# Deploying to production

Operator runbook for shipping `main` to the VPS at `81.0.219.165`
(`ssh wayneVPS`), where the app lives at `/var/www/africhart-emr` and serves
`africhartemr.com` plus every `<clinic>.africhartemr.com`.

> **The acceptance criterion is not "the deploy commands exited 0".** It is
> §6 — the browser check. Read that section before you start, because it is the
> step this project has twice skipped and twice regretted.

---

## 1. The mental model

The server is a **git checkout**, not a copy. You deploy by moving its `HEAD`,
never by editing files on the box. Anything hand-edited there will be silently
destroyed by the next `git checkout -f`, which is the point: the repository is
the only description of what production runs.

Two things are deliberately NOT in git and survive every deploy:
`.env` and `storage/`. Everything else is disposable and rebuildable.

### The two-database split you must keep in your head

| | migrations live in | applied by |
|---|---|---|
| Central (clinics, plans, users, `scheduled_task_runs`) | `database/migrations/central` | `php artisan migrate` |
| **Each clinic** (patients, consultations, invoices…) | `database/migrations/tenant` | **`php artisan tenants:migrate`** |

**`migrate` does not touch a single clinic.** A release whose migrations are
tenant migrations will report `Nothing to migrate` centrally and be completely
unapplied — every clinic then 500s the moment the new code selects a column
that does not exist there. Check which directory your migrations are in before
you deploy, and confirm the columns afterwards by looking (§5).

---

## 2. Preflight (local)

```bash
git status --short                      # nothing uncommitted that matters
COMPOSER_PROCESS_TIMEOUT=0 composer test:tenancy   # must be green
git push origin main
```

`COMPOSER_PROCESS_TIMEOUT=0` is not optional: the tenancy suite takes ~7
minutes and composer's default 300s timeout kills a passing run partway,
leaving orphaned test tenants behind.

Node must be ≥20 for the asset build. This machine uses `nvm`:

```bash
nvm use 22        # /usr/bin/node is 18 and is left alone deliberately
```

---

## 3. Deploy

Everything below runs as `wayne` on the box.

```bash
ssh -A wayneVPS       # -A forwards your agent so the box can fetch from GitHub
cd /var/www/africhart-emr

php artisan down --retry=60

git fetch origin main
git checkout -f -B main origin/main
chmod +x artisan                      # checkout resets the mode

composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build

php artisan migrate --force           # central
php artisan tenants:migrate --force   # EVERY clinic — see §1

php artisan config:clear && php artisan route:clear
php artisan cache:clear  && php artisan event:clear

php artisan queue:restart             # workers exit; supervisor restarts them on new code
php artisan up
```

### Do not add `config:cache` or `route:cache`

Production currently runs **uncached** config and routes — `bootstrap/cache/`
holds only `packages.php` and `services.php`. That is the state the box has
been verified in. Introducing caching is a real behaviour change (stale config
survives edits, and a cache built by the wrong user is unreadable by
`www-data`); make it a deliberate, separately tested change, not a side effect
of a release.

---

## 4. Who runs what — the permissions trap

The scheduler and the queue workers run as **`www-data`**. Per-tenant storage
directories are created by them, mode `0700`, owned by `www-data`:

```
drwx--S---  www-data www-data  storage/tenant<uuid>/
```

`wayne` is in the `www-data` *group*, but the group has no permissions on those
directories. So **any tenant-storage command you run by hand as `wayne` will
fail**, including `php artisan tenants:backup`:

```
Could not connect to disk local because:
League\Flysystem\UnableToCreateDirectory ... storage/tenant<uuid>/app/
```

That failure means *you are the wrong user*. It does **not** mean backups are
broken. Check the real, scheduled runs before concluding anything:

```bash
php artisan tinker --execute="foreach (\App\Models\ScheduledTaskRun::orderByDesc('id')->limit(10)->get()
  as \$r) { echo \$r->task,'  ',\$r->status,'  ',\$r->created_at,PHP_EOL; }"
```

### A safety net you can actually write

`mysqldump` needs no tenant storage, so it works as `wayne`. Take one before
any release that migrates:

```bash
cd /var/www/africhart-emr
DBU=$(grep ^DB_USERNAME= .env|cut -d= -f2-); DBP=$(grep ^DB_PASSWORD= .env|cut -d= -f2-)
OUT=~/predeploy-$(date +%Y%m%d-%H%M%S); mkdir -p "$OUT"
php artisan tinker --execute="foreach(\App\Models\Clinic::all() as \$c) echo \$c->tenancy_db_name,PHP_EOL;" \
  | grep africhart_tenant_ | while read DB; do mysqldump -u"$DBU" -p"$DBP" --single-transaction "$DB" > "$OUT/$DB.sql"; done
mysqldump -u"$DBU" -p"$DBP" --single-transaction africhart_central > "$OUT/africhart_central.sql"
wc -c "$OUT"/*.sql        # verify by contents, not by exit code
```

---

## 5. Verify the schema reached every clinic

Do not trust `tenants:migrate`'s own output. Look:

```bash
php artisan tinker --execute="
foreach (\App\Models\Clinic::all() as \$c) { \$c->run(function() use (\$c) {
  echo \$c->subdomain, '  ', (\Schema::hasColumn('patients','emergency_contact_name')?'ok':'MISSING'), PHP_EOL;
}); }"
```

Substitute the column your release actually added.

---

## 6. Verify the EXPERIENCE, in a browser — required

**A request returning 200 is not the same as the page working.** This project
has now shipped two defects that every status-code check passed:

- **A6** deployed a frontend where `asset()` emitted `/tenancy/assets/...`,
  which 500'd on every clinic subdomain. The *pages* returned 200 — only the
  CSS and JS they referenced failed. Every clinic ran with no styling and no
  JavaScript, and nobody saw it, because nobody opened a browser.
- The same shape had already appeared in the backup work: a command reporting
  `✓` while writing nothing.

So the deploy is not finished until a real browser has confirmed the page
*works*. Chrome is driven with `--host-resolver-rules` rather than `/etc/hosts`
so the `Host` header and TLS SNI stay exactly what a real visitor sends — and
so the check still runs when local DNS is down:

```js
chromium.launch({
  executablePath: '/usr/bin/google-chrome',
  args: ['--no-sandbox',
         '--host-resolver-rules=MAP alpha.africhartemr.com 81.0.219.165'],
});
```

### The checklist — all four must hold on a clinic subdomain

1. **No `/tenancy/assets/` requests at all.** One is a regression of the A6 bug.
2. **Every CSS and JS response is 200.** Collect them from `page.on('response')`;
   do not infer from the document's status.
3. **CSS is applied, not merely fetched** — assert a *computed* style, e.g.
   `getComputedStyle(document.body).backgroundColor === 'rgb(248, 247, 245)'`.
   Never assert on a stylesheet rule count; it varies harmlessly between builds.
4. **`typeof window.Alpine !== 'undefined'`** — this is the single check that
   caught the A6 defect. Every interactive control in this app is Alpine:
   the check-in modal, the vitals modal, the live queue, the prescription form.
   If Alpine is absent, the app looks fine and does nothing.

Then walk one real flow end to end as a signed-in user, and **confirm it in the
database, not in the rendered HTML** — the page can echo back what you typed
without having stored it.

### Writing the harness

Two failure modes will waste your time, and both are the harness's fault, not
the app's:

- **`waitUntil: 'networkidle'` never settles** on any page that polls (the
  queue, the consultation view, the billing list). Use `'load'`.
- **Several pages carry two submit buttons**, the first hidden. Scope every
  click to `button[type="submit"]:visible`.

And before every submit, dump `form :invalid` — otherwise a required field you
forgot (`blood_group`, `clinical_notes`) presents as a mysterious navigation
timeout that looks like a broken app:

```js
const bad = await page.$$eval('form :invalid', els => els.map(e => `${e.name}: ${e.validationMessage}`));
if (bad.length) console.log('blocked by browser validation:', bad.join(' | '));
```

---

## 7. If it goes wrong

`php artisan down` is still in effect until you run `up`, so take your time.

```bash
git checkout -f -B main <previous-good-sha>
composer install --no-dev --optimize-autoloader && npm ci && npm run build
php artisan config:clear && php artisan cache:clear && php artisan queue:restart
php artisan up
```

**Migrations do not roll back with the code.** A release that added columns
leaves them there; that is usually harmless (older code ignores them). If a
migration must be undone, `php artisan tenants:rollback --step=1` — and restore
from the §4 dump if it destroyed data.

If the site is stuck in maintenance mode after a failed run, the flag is just a
file: `rm storage/framework/down`.
