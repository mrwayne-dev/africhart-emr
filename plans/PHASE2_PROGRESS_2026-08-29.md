# Phase 2 — Progress Report #4

**Period:** 2026-08-29 (continues [`PHASE2_PROGRESS_2026-08-28.md`](PHASE2_PROGRESS_2026-08-28.md), which ends at `b1261e3`, before A4)
**Repo at:** `97aadf9` on `main`, pushed · 8 commits · 24 files · +1,887 / −87
**Production:** `81.0.219.165` (`vmi3509781`) — `main` live at `/var/www/africhart-emr`
**Companions:** [`ARCHITECTURE.md`](ARCHITECTURE.md) · [`PHASE2_SOW_TODO.md`](PHASE2_SOW_TODO.md) · [`../docs/PROVISIONING.md`](../docs/PROVISIONING.md) · [`../docs/BACKUPS_AND_OPS.md`](../docs/BACKUPS_AND_OPS.md)

Legend: ✅ done · 🟡 partial · ⬜ not started · ⚠️ decision or input needed

> **Every figure in this report was read from the repo or from a live run at the time of
> writing — none is estimated.** Commit hashes are `git log`; test counts are a real
> `composer test:tenancy` run; production figures are live queries against the box; HTTP
> codes are live requests to the public endpoints.
>
> **No prior report covers any of this work.** Report #3 ends at `b1261e3`, before A4.
> Until now the only record of A4, the Part 4 backup work and A6 was commit history.
> This report closes that gap.

---

## 1. Headline

**Phase A infrastructure is complete and proven on production.** Four sprints of work —
tenancy, per-tenant configuration, the invite system, backups, provisioning — met the real
VPS together for the first time, and the isolation guarantees that had only ever been
demonstrated in a test suite against a local MySQL were demonstrated on the public
internet, against the live deployment, over HTTPS.

**The boundary, stated plainly:** this was proven on **two throwaway test clinics**
(`alpha`, `bravo`) on a development-phase box with **no real patient data**. Onboarding a
real first clinic is a separate step and is **still gated on the two A5 backup gates**.
*Phase A infrastructure is done* does **not** mean *a real clinic is live*.

| | |
|---|---|
| SOW items completed | **A4** ✅ · **A6** ✅ (infrastructure) · **A3** ✅ · **A2** ✅ (corrected from a stale 🟡) |
| Still open in Phase A | **A5** 🟡 — two gates, now **pre-real-clinic**, not pre-A6 |
| Defects found and fixed | **7**, of which **4** were failures that reported success |
| Test suite | **65 tests, 337 assertions** — unchanged; this period's proof was live runs, not new tests |
| Production | 2 clinics · 20 tables each · **0 fictional patients** · AES-256 per-tenant backups |
| Blocked on the Client | The backup account for off-site storage · the nine Paystack questions |

The through-line of this period is unchanged from report #3, and worth restating because
it kept paying: **four of the seven defects found were things that reported success while
doing nothing, or reported failure while leaving wreckage.** None would have been caught
by reading an exit code.

---

## 2. The work, commit by commit

| Commit | | Files | Δ |
|---|---|---|---|
| `0443977` | Part 4 backup prerequisites — S3 driver, archive encryption, per-tenant S3 isolation | 4 | +368 / −2 |
| `9575dff` | Tenant-aware backup monitor and cleanup, and detecting the monitor's own silence | 6 | +455 / −42 |
| `53b481f` | Name the registered company, publish the CAC certificate at `/legal` | 9 | +219 / −8 |
| `79fe883` | Record the two backup gates so A6 cannot skip them | 1 | +46 / −9 |
| `fa811b0` | Ignore per-tenant storage roots | 1 | +5 |
| `9e2e4b8` | **A4** — `tenant:create`, provision end to end or leave nothing behind | 2 | +697 |
| `d5d89e9` | Record the A6 cutover and the timezone skew it surfaced | 1 | +37 / −1 |
| `97aadf9` | Reconcile the SOW with what A2, A3, A4 and A6 actually delivered | 2 | +62 / −27 |

`53b481f` is outside this report's main narrative — it names the registered company and
publishes the CAC certificate on `/legal`, closing a B7 compliance detail.

---

## 3. A4 — `tenant:create` (`9e2e4b8`) ✅

The provisioning command. Mostly **assembly**: `Clinic::create()` already fired
`TenantCreated`, whose pipeline creates the database and runs the 18 tenant migrations
synchronously; the reserved-subdomain blocklist was already enforced by the model's own
`saving` hooks; `clinic:invite` already issued a first admin. What was missing was one
door that does them in order, refuses to start when it cannot finish, and cleans up after
itself when a step fails halfway.

### 3.1 🔴 The failure it exists to prevent — measured before it was written

Rather than reason about what a partial failure would leave behind, a deliberately
throwing tenant migration was installed and `Clinic::create()` called. The result:

```
EXCEPTION: RuntimeException — FAILURE PROBE: simulated migration failure
registry row exists: YES  id=23d0f3d2…
tenant databases now: 3   ← the orphan, 20 tables
RETRY BLOCKED: SQLSTATE[23000] Duplicate entry 'probeclinic' for key 'clinics…'
```

**Three failure modes from one bad migration:** a stray registry row, an orphaned
database, and a subdomain permanently taken. Worse than the count suggests — the probe ran
*last*, so the database had all 20 tables. A migration failing early leaves a *partially
migrated* database, because MySQL DDL is not transactional.

### 3.2 🔴 The finding that matters most — the rollback lied on its first run

The command was written, and the same throwing-migration test run against it. It reported:

```
Provisioning failed: FAILURE PROBE: simulated migration failure
Nothing to undo — the failure happened before anything was created.
The subdomain [doomed] is still free.
```

**Every word of that was false.** Checking the contents rather than believing the output:

```
registry row for doomed: EXISTS id=6c84f7f3…
tenant databases: 4     ← the orphan, 20 tables
```

**Why:** `Clinic::create()` throws from *inside* the `TenantCreated` event pipeline —
after the row is committed and the database built — so the assignment never completes and
`$clinic` is still `null` in the `catch`. The code trusted its own local variable.

**The code written to prevent a false report of clean state produced one.** That is the
third time this project has met a failure that reports success, and the first time inside
the prevention itself.

**The fix:** don't trust the variable. The tenant id is now chosen *up front*, so the
wreckage can be looked up by it (`Clinic::find($tenantId)`), and a surviving database is
checked for even when no row remains. It was caught only because the acceptance criterion
was *verify by contents*, not *read the exit code*. Had the command's own output been
trusted, this would have shipped.

### 3.3 Design decisions, and why

| Decision | Reasoning |
|---|---|
| **Rollback = compensating deletion**, labelled as such in the code | It cannot be atomic and pretending otherwise would be the lie: stancl fires `TenantCreated` *after* the row commits, so there is no open transaction; and DDL is not transactional, so a migration failing on file 12 of 18 leaves eleven tables. It deletes what it made and then **verifies by looking** |
| **Idempotency by refusal**, not convergence | Re-running for an existing subdomain names the clinic that holds it, rather than surfacing a raw `1062`. Provisioning twice is an operator mistake; the right answer is to say so |
| **Exit code 2** for "provisioned, not invited" | `0` would let a script pass over a half-finished onboarding in silence — the exact failure shape this project keeps finding. `1` would invite a caller to treat a healthy clinic as broken |
| **A failed invite does not roll the clinic back** | A provisioned clinic with no admin yet is good and recoverable in one command. Destroying it over a bounced email turns a small problem into data loss. The output says so unmistakably: *the clinic is healthy, do not tear it down*, followed by the exact `clinic:invite` line |
| **No demo data, ever** | Seeds `MedicationSeeder` only — a starter drug catalogue is reference data. Never `TenantDatabaseSeeder`, which carries invented staff and **fictional patients**; seeding that into a real clinic would put invented people in a medical record |

### 3.4 Verified by running

| Check | Result |
|---|---|
| **Rollback** (throwing migration) | "Rolled back cleanly" — **independently confirmed**: row gone, **0 orphan databases**, subdomain then successfully re-provisioned |
| Provision end to end | Reachable over HTTP; invite page renders the clinic and invitee; token **404s at another clinic** |
| No demo data | **0 patients, 0 staff, 0 consultations, 0 invoices** · 10 catalogue drugs, 4 settings, 1 invitation |
| Failed invite | Both shapes — non-zero return **and** thrown exception — leave a healthy clinic and print the recovery line, which was then run and worked |
| Re-run existing subdomain | Refused in preflight naming the existing clinic, exit 1, nothing created |
| Reserved subdomain | `api` refused — inherited from the model, not re-implemented |

Also caught in passing: `owner_name` and `owner_email` are `NOT NULL` on `clinics`, so both
are required in preflight rather than arriving as a `1048` halfway through provisioning.

`docs/PROVISIONING.md` is the operator runbook — steps, what to check, exit codes, and the
recovery paths for a failed invite and a failed rollback.

---

## 4. Part 4 — off-site backup prerequisites (`0443977`) ✅

Scoped as *"credentials + verification, not new code"* because `config/backup.php` already
had the S3 destination wired. **It was not.** Three defects sat between that config and a
working off-site copy, each of which would have failed on the day credentials arrived.

### 4.1 🔴 The `s3` driver did not exist

`league/flysystem-aws-s3-v3` was never installed. `Storage::disk('s3')` threw at disk
resolution. The destination pointed at a driver that was not there.

### 4.2 🔴 Archive encryption was silently off — plaintext patient data

`BACKUP_ARCHIVE_PASSWORD` was absent from `.env` **and** missing from `.env.example`,
while `config/backup.php` reads it. **spatie treats a null password as "no encryption",
with no warning and no error.** Every local archive was plaintext, and a fresh setup from
the example file would have inherited the same gap with nothing to prompt otherwise.

For a system whose archives contain complete patient records, this is the most serious
defect of the period.

### 4.3 🔴 Per-tenant isolation did not hold off-site

`'s3'` was commented out of `tenancy.filesystem.disks`. On disk each clinic is isolated
because stancl's bootstrapper suffixes `storage_path()`; the S3 disk is only prefixed if
it appears in that list. Every clinic's archive would have landed under the **same bucket
prefix**, separated only by filename — **the off-site copy less isolated than the local
one it exists to protect.**

Proven load-bearing with a counterfactual run into a second bucket:

```
without the fix   africhart-emr/grace-….zip        ← one shared prefix
                  africhart-emr/hope-….zip
with the fix      tenant39c0f29e…/africhart-emr/hope-….zip    ← per clinic
                  tenantcf44c613…/africhart-emr/grace-….zip
```

### 4.4 The round trip, proven by contents

With no credentials available, a local S3-compatible endpoint was stood up and the app
pointed at it through shell environment variables — which override `.env` — so **no
placeholder credentials were ever written to the file**.

| Step | Evidence |
|---|---|
| Backup ran | `✓ hope ✓ grace`, 0 failed |
| Landed in object storage | **2 objects listed in the bucket**, under separate tenant prefixes |
| Encrypted at rest | `AES-256`, `WzAES`; **wrong password → 0-byte file**; 0 plaintext `INSERT INTO` in the stored object |
| Retrieved | Downloaded from the bucket; local copies deliberately ignored |
| Restored | Imported to scratch databases, 20 tables each |
| Data verified | **All 20 table checksums and all 185 columns identical to live**, per clinic |
| Isolation | **0** rows carrying the other clinic's ID prefix |
| Actually working | Reads through Eloquent, accepts writes, still enforces `patients_patient_id_unique` |

**A near-miss worth recording:** a comparison query used a non-existent column and printed
**"IDENTICAL (0 rows)"** — a green line from a query that selected nothing. It was replaced
with per-table checksums and an `information_schema` column diff. Same family as an
archive reporting ✓ while dumping the wrong database.

### 4.5 ⚠️ Off-site itself: deferred, not dropped

The destination is **not wired**. This changed on budget grounds: no longer Contabo Object
Storage (paid, and blocked on client credentials that were never issued), now planned as
**Google Drive via `rclone` — a free destination**.

**A lost VPS is currently a lost backup.** That is acceptable now — throwaway clinics, no
real patient data — and stops being acceptable at the first real clinic. It is a
**pre-real-clinic gate**, tracked in A5.

---

## 5. The backup monitor and cleanup (`9575dff`) ✅

Both commands acted on the **central** disk, which is empty — every archive this system
produces is per-tenant. Both therefore succeeded nightly while doing nothing.

### 5.1 🔴 A fictional health check, vouched for by documentation

`backup:monitor` reported:

> *"There are no backups of this application at all."*

...while **seven healthy archives** sat on the box. Outside tenancy the `local` disk points
at the central storage path. Worse, it **was not scheduled anywhere** — `routes/console.php`
scheduled `BackupTenants`, `backup:clean`, `AuditTrials` and `AuditScheduler`, and no
monitor — while `docs/BACKUPS_AND_OPS.md` claimed it ran nightly at 03:00.

**This is the Sprint-0 pattern exactly:** a health check that does not run, would answer
wrongly if it did, and has documentation vouching for it. That is worse than no health
check, because it spends the trust that would otherwise go to looking.

The mechanism was verified rather than assumed: the *identical* `backup:monitor`
invocation returns "unhealthy, no backups at all" centrally and "healthy" inside a clinic,
against the same config. What changes per tenant is the **disk root**, not the backup
config — which is also why the monitor needs no `--config` and, unlike `backup:clean`, has
none to give.

### 5.2 🔴 `backup:clean` pruned nothing, for ever

It cleaned the central directory — empty — while each clinic's directory grew without
bound: a daily archive per clinic, kept for ever, on one VPS disk. The same shape as the
backup bug it sits beside, with a slower fuse.

**The trap avoided:** `backup:clean` *does* take its `Config` by constructor injection, and
Artisan caches a resolved command for the process lifetime, so clinic #2 would inherit
clinic #1's config. Proven avoided by **contents**: a central control directory was created
alongside the tenant fixtures and **survived untouched** while both clinics were pruned.

### 5.3 Proven to catch failure — not just to say "healthy"

A monitor only ever seen saying healthy is the same as no monitor.

| Scenario | Result |
|---|---|
| Both clinics recent | `✓ hope ✓ grace` — exit **0** |
| hope's archive removed | `✗ hope — no backups at all`, **grace still ✓** — exit **1** |
| grace's archive aged 3 days | `✗ grace — latest backup … too old`, **hope still ✓** — exit **1** |
| Cleanup | 3 same-week archives 44–46 days old per clinic → **2 pruned, 1 kept**, today's kept, **central control untouched** |

**Silence detection, end to end:** the monitor is itself in `schedule:audit`'s
expectations, so with its rows deleted `schedule:audit` exits 1 and **`/up` returns 500**;
after a real run, **200**. The monitor is monitored.

Success notifications were turned off (failures still notify): these events fire once per
*clinic* across three tenant-iterating tasks, so leaving them on meant one mail per clinic
per task per night, scaling with signups — and nobody reads the seventh day of that, which
is how the one saying FAILED gets read as noise.

### 5.4 🔴 The documented restore could not have worked

`docs/BACKUPS_AND_OPS.md` described an **`unzip`-based** restore. Info-ZIP **cannot read
WinZip AES archives at all** — it fails in a way that looks like a corrupt file. The
runbook for the most safety-critical operation in the system described a procedure that
would have failed at the moment it was needed.

This is the **Sprint-0 `unzip` trap resurfaced in documentation** — the same trap that made
this project's first encryption check "pass" only because `unzip` was not installed.

The doc is corrected to `7z`, along with the schedule table (which listed the three central
spatie commands rather than what runs) and a dump filename that predated tenancy. It now
also states plainly that **there is no off-site copy today**.

**A corrected doc is a claim, not evidence.** Which is precisely why the restore drill must
be *executed* against a real AES-encrypted archive — the second pre-real-clinic gate.

---

## 6. A6 — production cutover ✅ (deploy through `d5d89e9`)

The first time four sprints of work met the real box together. Run as four phases:
investigate, deploy, prove, tear down — with the plan reviewed before the box was touched.

### 6.1 🔴 The blocker Phase 1 found: MySQL privileges

The app user held exactly:

```
GRANT USAGE ON *.* TO `africhart_smoke`@`localhost`
GRANT ALL PRIVILEGES ON `africhart_smoke`.* TO `africhart_smoke`@`localhost`
```

Tenancy's `CreateDatabase` needs **global `CREATE`/`DROP DATABASE`**. Proven, not inferred:
`CREATE DATABASE africhart_privprobe` → `ERROR 1044 Access denied`. **The very first
`tenant:create` on the box would have failed at step 3.**

Fixed with a **least-privilege** grant rather than `GRANT ALL ON *.*`:

```
GRANT CREATE, DROP ON *.*                        TO `africhart`@`localhost`
GRANT ALL PRIVILEGES ON `africhart_central`.*    TO `africhart`@`localhost`
GRANT ALL PRIVILEGES ON `africhart\_tenant\_%`.* TO `africhart`@`localhost`
```

Tenancy must create and drop whole databases, which MySQL can only grant globally; data
rights are confined to central plus the tenant name pattern. The pattern **matched real
tenant database names** — creation *and* migration both succeeded under least privilege,
which was the main thing that could have failed.

Also found: MySQL root on this box is **not** socket-auth (all accounts use
`caching_sha2_password`), so DDL went through `/etc/mysql/debian.cnf`.

**What was already right, verified rather than assumed:** PHP 8.3.33 with every required
extension; nginx 1.24 already carrying `*.africhartemr.com`; a **valid wildcard certificate**
(`DNS:*.africhartemr.com`, expires **Nov 13 2026**); DNS wildcard resolving; composer, npm,
git, rsync and **7z** all present; the scheduler cron already firing every minute; 93G free.

### 6.2 🔴 The verification harness tested the wrong deployment

Early HTTPS checks used `curl --resolve …:8443:` with URLs that **omitted the port**, so
curl ignored the mapping, connected to the public `:443`, and was testing **the old smoke
deploy** while appearing to test the new one. It produced a convincing but false picture:
`/login` 200 (the *old* app's login), `/` 200 marketing, invites 404-ing at their own clinic.

**It was caught because the signals did not cohere.** The router matched `invite.show`; the
token hashed to the stored row; `findPending` found it — yet HTTP said 404. Running the
request in-process through the real middleware stack returned **200 with tenant `alpha`**,
which located the fault in the harness rather than the app. Every check was re-run with
`--connect-to`, which keeps the Host header clean.

Had this not been caught, the isolation proof would have been *worthless* — measurements of
the deployment being replaced, reported as measurements of the new one.

### 6.3 Storage ownership — resolved by writing, not by trusting a bit

Flagged in Phase 1: `tenant:create` run as `wayne` would create per-tenant storage owned by
`wayne`, while php-fpm and the queue worker run as `www-data`. Provisioning was therefore
run **as www-data**.

Two of my own checks were wrong before one was right — a glob that did not expand (creating
a literal `storage/tenant*` directory, since removed) and an assertion against
`app/private/` when tenancy overrides the disk root to `app/`. Asking the disk for its own
path: **www-data wrote and read back** in each clinic's separate root. The setgid bit was
never taken as sufficient evidence.

### 6.4 🔴 The cron near-miss at cutover

A malformed `sed` during the repoint prefixed **every line of
`/etc/cron.d/africhart-scheduler`** with junk — which would have **silently killed the
scheduler**, the precise failure this project has now met three times. It was rewritten
cleanly and cron restarted. Confirmed firing by **observing the log advance**
(`mtime 23:56:01` against a check at `23:56:52`), not by assuming a valid file works.

### 6.5 Isolation re-proven on the real public `:443`

With genuine logins over the internet, not loopback:

| Probe | Result |
|---|---|
| Tenant front door `/` | **302** → `/dashboard` (the old app served 200 marketing) |
| Unknown subdomain `/`, `/login` | **404 / 404** (the old app served 200) |
| `/legal` — exists only in `main` | **200** — a decisive old-vs-new discriminator |
| alpha invite @ alpha / @ bravo | **200 / 404** |
| bravo invite @ bravo / @ alpha | **200 / 404** |
| **Positive control** — each session at its own clinic | **200**, showing the right admin |
| **Cross-tenant session** — alpha cookie → bravo, and the reverse | **302**, redirected to *that* clinic's login |
| Sessions | central **0**; per-tenant, one authenticated each |
| Patients | `ALPH-20260829-0001` / `BRAV-20260829-0001`, **0** rows with the other's prefix |

Both invitations were **accepted over HTTPS** — real staff accounts created, invitations
consumed. Both clinics minted `-0001` independently, which is exactly what the old global
prefix would have collided on.

Backups on the box: **AES-256**, each archive containing **only its own** tenant database,
**0** plaintext `INSERT`s. Monitor healthy → alpha's archive removed → `✗ alpha` while
`✓ bravo` → restored. `/up` 200 → monitor silenced → **500** → re-run → **200**.

The positive control matters: without it, "302 everywhere" would have proven nothing.

### 6.6 The teardown, in the only safe order

**Dump → repoint → verify on `:443` → only then drop.** A `mysqldump` of `africhart_smoke`
was taken first — **63,265 bytes, 19 `CREATE TABLE`, 14 `INSERT`, clean trailer** — with a
second non-root-readable copy, and the teardown script re-checked its size and would have
aborted below 10KB. The old database, its MySQL user and `/var/www/africhart-smoke` were
removed **only after** the public `:443` was confirmed serving the new deploy, and the
temporary verification listener was removed with them. Post-teardown re-verification
confirmed nothing had depended on the old deploy.

### 6.7 ⚠️ Stated honestly: invites are not proven by email

`MAIL_MAILER=log` on the box. The first-admin invite is proven by **the printed link and
HTTP acceptance** — not by a received email. Nothing in the mail path beyond the transport
is stubbed: invitation records, tokens, expiry and acceptance are all real and working.

### 6.8 Production, read live at the time of writing

| | |
|---|---|
| Clinics | 2 — `alpha`, `bravo`, 20 tables each |
| Patient data | 1 isolation-probe patient per clinic · **0 fictional/demo records** |
| Staff | 1 per clinic, created by accepting a real invitation |
| Catalogue | 10 starter medications per clinic |
| Public endpoints | `/` 200 · `/up` **200** · `/legal` 200 · `alpha` 302 · unknown subdomain 404 |
| Mail | `log` — no delivery |
| Off-site backup | **none** — `["local"]` only |

---

## 7. Corrected SOW status

Checked against each item's detail section, not carried forward from the summary — that is
the staleness `97aadf9` removed.

### Phase A

| | | |
|---|---|---|
| **A1** Tenancy architecture | ✅ | Complete 2026-08-25 |
| **A2** Per-tenant configuration | ✅ | Complete 2026-08-28. **Corrected this period** — its summary row still called invite codes a hard gate while its own section read "COMPLETE" |
| **A3** VPS infrastructure | ✅ | Complete 2026-08-29, proven by the cutover. Off-site storage is **deferred by decision**, not outstanding work — it moved to A5 |
| **A4** Provisioning command | ✅ | Complete 2026-08-29 (`9e2e4b8`), rollback proven by forcing a partial failure |
| **A5** Backups | 🟡 | Per-clinic backup, cleanup and monitoring built and verified by contents, on production as well as locally. **Two gates left — now PRE-REAL-CLINIC, not pre-A6** |
| **A6** Tenant #1 | ✅ | Complete 2026-08-29 **as the infrastructure milestone**, on two throwaway clinics. ⚠️ Onboarding a **real** first clinic is separate and still gated on A5 |

### Phase B — unchanged, and now unblocked

**B3** ✅ complete · **B4** 🟡 two seams shipped, Settings hub absent · **B7** 🟡 legal docs
written and the company now named on `/legal` (`53b481f`); the **data-isolation guarantee
is now demonstrable on production**, not only in a test suite · **B1 · B2 · B5 · B6** ⬜.

---

## 8. Test suite

**65 tests, 337 assertions, all passing** — verified by a real `composer test:tenancy` run
against MySQL while writing this report.

**Unchanged from report #3, and deliberately so.** This period's work was a provisioning
command, backup operations and a deployment — things whose failure modes are orphaned
databases, unencrypted archives, unscheduled monitors and wrong-target verification. None
of those is caught by a unit test. **Every proof in this report is a live run whose
contents were inspected**, which is the standard this project settled on after an archive
reported ✓ while dumping the wrong database.

One suite trap found: `composer test:tenancy` carries no timeout override, so composer's
300s process limit **kills a passing suite** (~380s) mid-run and strands test rows, which
then fail the *next* run. Use `COMPOSER_PROCESS_TIMEOUT=0`. Not yet fixed in the script.

---

## 9. Open items and risks

### ⚠️ The two pre-real-clinic gates — client-blocked

1. **Off-site backup destination.** Google Drive via `rclone` (free), replacing Contabo on
   budget grounds. **Blocked on the Client** for the backup Google account. *A lost VPS is
   currently a lost backup* — acceptable with throwaway clinics, not with a real one.
2. **Execute the restore drill for real** on an AES-encrypted archive. The documented
   procedure could not have worked; the doc is corrected but that is a claim, not evidence.

### ⚠️ The PHP/MySQL timezone skew — before real data

PHP runs UTC while the MySQL session runs `+02:00`: `now()` returned `21:47` against
`SELECT NOW()` of `23:47` on the same box. Nothing observed breaks today, because every
timestamp in play is written by PHP — but a column taking a SQL-side default would
disagree with an application-written one by **two hours in the same row**. The table where
that matters most is `audit_logs`. **This is an audit-trail integrity issue.**

### ⚠️ The scheduler's outermost alarm is still unarmed

`/up` returns 500 on silence and 200 when healthy — proven on production. **Nothing polls
it.** Until an external uptime monitor does, the dead-man's switch is not connected. This
is a go-live task, unchanged since report #3.

### ⚠️ Get Started promises an email nobody sends — deferred to B5

`POST /signup` writes one `marketing_leads` row and flashes a toast. That is the whole
action — `MarketingLead` appears in exactly two places in `app/`, with no observer or
notification — while the page promises three times to email login details. **No route or
view surfaces leads either**, so the promise depends on an operator noticing a row nothing
tells them about. Two fixes, both B5: an operator notification, and honest copy.

### ⚠️ Blocked on the Client, unchanged

The **nine Paystack architecture questions** in SOW §6 must be worked through before any
billing code is written. The gateway is locked; the design is not.

### Deployment state

`main` is live at `/var/www/africhart-emr`. The pre-tenancy smoke deploy is **gone** —
database, MySQL user and files. Mail is `log`. No off-site backup.

---

## 10. Suggested sequence from here

1. **The two backup gates** — off-site via `rclone`, then the restore drill executed for
   real. Nothing should cross the real-data threshold until both close.
2. **The timezone skew** — cheap, and an audit-trail correctness issue.
3. **B4 — Settings hub.** The groundwork exists (the tenant `settings` table and the
   per-clinic values A2 shipped), so this is the cheapest real progress.
4. **B1 + B2 — billing.** The nine Paystack questions **first**.
5. **B5 + B6 + B7** — super-admin (with the Get Started fixes) → telemetry → DPA and the
   isolation guarantee, which is now backed by evidence gathered on production.

**Then, and only then, a real first clinic.**

---

*Written 2026-08-29 against `97aadf9` on `main`. Production read live at
`81.0.219.165`. This report is the first written record of A4, the Part 4 backup work and
A6 — before it, that record was commit history alone.*
