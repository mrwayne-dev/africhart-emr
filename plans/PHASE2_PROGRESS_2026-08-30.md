# Phase 2 — Progress Report #5

**Period:** 2026-08-30 → 2026-08-31 (continues [`PHASE2_PROGRESS_2026-08-29.md`](PHASE2_PROGRESS_2026-08-29.md), which ends at `9fa3143`)
**Repo at:** `536567f` on `main`, pushed · 4 commits · 17 files · +659 / −12
**Production:** `81.0.219.165` (`vmi3509781`) — `main` live at `/var/www/africhart-emr`, **now a real git checkout**
**Companions:** [`ARCHITECTURE.md`](ARCHITECTURE.md) · [`PHASE2_SOW_TODO.md`](PHASE2_SOW_TODO.md) · [`../docs/DEPLOYMENT.md`](../docs/DEPLOYMENT.md) · [`../docs/PROVISIONING.md`](../docs/PROVISIONING.md)

Legend: ✅ done · 🟡 partial · ⬜ not started · ⚠️ decision or input needed

> **Every figure in this report was read from the repo or from a live run at the time of
> writing — none is estimated.** Commit hashes and diffstats are `git log`; the test count
> is a real `composer test:tenancy` run; production figures are live queries and live HTTP
> against the box; the browser findings are a real Chrome session driven over the public
> internet.

---

## 1. Headline

**The clinical workflows were tested end to end for the first time, and then — because
testing them required a working browser — we discovered that production had been serving
broken pages since the day it went live.**

Report #4 closed Phase A with the infrastructure proven on production: isolation, backups,
provisioning, TLS, all demonstrated by live runs against the real box. Every one of those
proofs was true. **None of them looked at a page.**

This period did, and found that **every clinic subdomain had been serving pages with no
CSS and no JavaScript since the A6 deploy** — invisibly, for a full day, because the pages
themselves returned `200`. Only the assets they referenced were failing.

| | |
|---|---|
| Workflows proven end to end | **register → queue → vitals → consult → prescribe → invoice → dashboard**, four roles, zero 500s |
| Defects found | **7** — 5 from the workflow walk-through, 1 (the severe one) from the browser check, 1 caught by reading code before it ran |
| Defects fixed and deployed | **6**. The seventh — clinic identity on screen — is **B4 by design**, not a regression |
| False findings caught in our *own* method | **5** — 3 in the walk-through harness, 2 in the deploy |
| Test suite | **65 tests, 337 assertions**, 500.3 s — unchanged and deliberately so (§8) |
| Production | `536567f` · 2 clinics · 20 tables each · **0 patients** · assets `200` · Alpine running |

**The through-line of this project is that things which report success are the dangerous
ones.** Report #4 counted four failures that reported success. This period found a fifth
and worst example — and it was *our own verification* that was reporting success.

---

## 2. The work, commit by commit

| Commit | Subject | Files | Lines |
|---|---|---|---|
| `7de26c5` | `fix(audit)`: make the queue auditable and name invoice events instead of "updated" | 2 | +124 / −6 |
| `1535a73` | `feat(patients,prescriptions)`: emergency contact, and prescriptions that can reference the catalogue | 13 | +281 / −5 |
| `a58fa3f` | `fix(assets)`: stop routing `asset()` through the tenant asset controller | 1 | +20 / −1 |
| `536567f` | `docs`: add the deploy runbook, with a required browser check | 1 | +234 |

Two of the four are one-line behavioural changes wrapped in explanation. That ratio is
deliberate: `a58fa3f` changes a single boolean, and the twenty lines around it exist so
nobody switches it back.

---

## 3. The clinic-day walk-through — the workflows work

Before B4 builds settings screens *on top of* the clinical workflows, the workflows
themselves needed proving. A throwaway clinic was provisioned and a full clinical day
driven **over real HTTP as four different signed-in roles** — admin, doctor, nurse,
receptionist — with no `actingAs()` shortcuts, because a policy that passes in a test
helper can still fail behind a real session cookie.

**Result: the clinical day works.** Register → queue → vitals → consult → prescribe →
invoice → dashboard completed end to end, **zero 500s**, every step verified by reading
the database rather than the rendered page.

### 3.1 Both previously-broken flows are healed

Two flows had been broken earlier in the project's life and were re-tested deliberately:

- **`93c9609`** — check-in and doctor assignment 500'd after the `users` → `staff` rename,
  because three validation rules still pointed at the old table. Both now work.
- **`6b38e75`** — doctors could not complete or edit **their own** consultations. MySQL
  returns uncast FK columns as strings, so `$user->id === $consultation->doctor_id` compared
  `4 === "4"` and was false; the Complete button never rendered. Admins were unaffected
  because `isAdmin()` short-circuits first, which is exactly why it survived so long.
  Confirmed fixed: `Consultation::casts()` declares `patient_id` and `doctor_id` as
  `integer`, and a doctor completed their own consultation in this run.

### 3.2 🔴 The punch-list it surfaced

Five gaps, none of which stop the day working, all of which matter before B4:

1. **The nurse was invisible in the audit trail.** Check-in and vitals — the nurse's entire
   contribution to the record — wrote nothing to `audit_logs`. `PatientQueue` simply did not
   use the audit trait.
2. **Invoice states were indistinguishable.** Issuing an invoice and marking it paid both
   logged `Updated invoice …`. An audit trail that cannot tell "billed" from "money taken"
   is not an audit trail for the one table where that distinction is the point.
3. **Prescriptions were free text with nothing behind them.** The drug catalogue was
   decorative: the walk-through prescribed `Notarealdrug Zzyzx 500mg` and it was accepted.
4. **No emergency contact on the patient record.** A clinic cannot reach anyone for a
   patient who arrives unable to speak for themselves.
5. **Clinic identity is not on screen.** Present on printed documents (A2), absent from the
   interface. → **B4**, not fixed here.

### 3.3 Three false findings caught in the harness, before they were reported

Worth recording, because each would have been filed as an application bug:

- **`curl -d` does not URL-encode.** A patient name with a space broke the POST. It
  presented as an app validation failure; it was the tester's own request. Fixed with
  `--data-urlencode`.
- **`$BODY` was unset inside a subshell**, so `grep` read from stdin and returned 0 — which
  read as *"the nurse cannot see the queue"*, a serious-sounding isolation defect.
  Re-checked: the nurse sees the queue correctly. **No bug.**
- **An invented column.** A `?? null` fallback referenced `completed_at`, which does not
  exist on that table, producing a "field is never set" finding about a field that was
  never in the schema.

**Every one of these looked like a defect in the application and was a defect in the
method.** This is why findings in this project are confirmed by reading the database
before they are written down.

---

## 4. The pre-B4 fixes — `7de26c5` and `1535a73`

### 4.1 Audit-trail completeness (`7de26c5`, 2 files, +124 / −6)

`PatientQueue` now uses `HasAuditTrail`, and both models name what actually happened
instead of logging `updated`. `PatientQueue::auditDescription()` inspects `wasChanged()`
and distinguishes check-in, vitals, doctor assignment and status change; `Invoice` does the
same for status transitions.

The queue's `updated` event covers several genuinely different clinical acts, so the
ordering is most-specific-first — vitals before doctor assignment before a bare status
change — which is why the vitals branch tests `wasChanged('vitals_recorded_at')` *and* the
individual vitals columns.

### 4.2 🔴 A payment-breaking bug caught before it ran

The first draft of the Paid branch interpolated the payment method directly:

```php
"Marked invoice {$this->invoice_number} PAID … by {$this->payment_method}"
```

`payment_method` is a `PaymentMethod` **enum**, and interpolating a non-backed enum throws.
This would have thrown **inside the audit write, inside `markAsPaid()`** — so the failure
would not have been a cosmetic log problem. **Marking any invoice as paid would have
500'd**, and it would have appeared the moment real money was first recorded.

Caught by reading the code before running it, and fixed with an enum-safe
`paymentMethodLabel()`. Verified afterwards on production: the live audit row reads
`Marked invoice ALPH-INV-20260830-0001 PAID — ₦8,000.00 by Cash`.

### 4.3 Patient model — emergency contact and gender (`1535a73`)

Four columns added to `patients`: `emergency_contact_name`, `emergency_contact_phone`,
`emergency_contact_relationship`, and `gender`. The relationship field is separate on
purpose — "who to call" is much less useful than "who to call, and who they are to the
patient" when the call is being made under pressure.

### 4.4 Prescriptions that can reference the catalogue — by design, *both*

`prescriptions.medication_id` is nullable and `constrained('medications')`. The design
decision is that **both** modes are first-class:

- `medication_id` set when the doctor picks from the catalogue — structured, so renaming a
  catalogue entry stays consistent instead of silently desynchronising every prescription
  that copied its old name.
- `medication_name` **always** kept. It is what was actually prescribed, and it is the only
  record for a drug the clinic does not stock. Doctors must be able to prescribe
  off-catalogue; a system that forbids it is one they route around.

**`nullOnDelete`, never cascade.** Removing a drug from the catalogue must not delete a
prescription — the prescription is a clinical record of what a patient was told to take, and
it outlives the catalogue entry. It reverts to free text. *Verified live in production's
schema:* `prescriptions.medication_id -> medications.id ON DELETE SET NULL`.

**The backfill was proven, not assumed.** Existing rows are all free text, so the migration
links those whose name matches a catalogue entry exactly. That path was tested by rolling
the migration back on a real tenant, planting a free-text row, and re-running it —
rollback-plant-rerun, rather than trusting a path that only executes on data that no longer
exists by the time anyone looks.

---

## 5. 🔴 The headline finding — production had no JavaScript

### 5.1 How it was found: by needing Node, not by looking for it

The prescription-linking UI in §4.4 is Alpine-driven, and had never been run in a browser.
Checking it required building the frontend, which required a newer Node — the shell was on
Ubuntu's `/usr/bin/node` v18. `nvm` was already installed with v22 and v24 available but
inactive, so switching cost nothing and left `/usr/bin/node` untouched for other projects.

Node v22.23.2 / npm 10.9.8. `npm ci` added 68 packages; `npm run build` produced
`app-Bgx0rOE9.js` (255,288 bytes) and `app-DMsfa75y.css` (74,689 bytes).

Then a real Chrome session — driven with `--host-resolver-rules` so the `Host` header and
TLS SNI stay exactly what a real visitor sends — loaded the consultation page and reported:

```
Alpine present in the browser: false
[console error] Failed to load resource: 500 (Internal Server Error) ×2
```

### 5.2 The cause: a correct architectural decision that silently broke a vendor route

`tenancy.filesystem.asset_helper_tenancy` was `true`, so `asset()` on a tenant subdomain
rewrote every URL to `/tenancy/assets/…`, served by stancl's `TenantAssetsController`. That
controller calls `$tenant->domains()`.

**`Clinic` deliberately does not have that relation.** The `domains` table was dropped in A1
so that `clinics.subdomain` is the single source of truth for addressing — a decision that
is right, documented in the model, and load-bearing. It also, silently, broke a vendor route
nobody had reason to think about.

```
production.ERROR: Call to undefined method App\Models\Clinic::domains()
```

The apex domain was fine throughout (`/build/…` → `200`), which is why the marketing site
looked healthy while **every clinic page ran with no styling and no JavaScript**. In this
app that is not cosmetic: the check-in modal, the vitals modal, the live queue and the
prescription form are all Alpine. The application looked entirely normal and did nothing.

### 5.3 Why A6 missed it — the page, not the experience

A6 verified HTTP status codes and HTML content. Both were correct: the *documents* returned
`200`. What failed were the sub-resources those documents referenced, and nothing in the
verification followed them.

**This is the second A6 verification gap of the same shape.** The first was the harness that
tested the wrong deployment (report #4 §6.2) — `curl --resolve` with portless URLs silently
hit the old smoke deploy. Both times the check ran, passed, and was measuring something
other than what it claimed.

> **A request returning is not the same as the page working.**

The defect had been live since the A6 deploy. The log holds only three occurrences of it,
all triggered by this investigation — because in the intervening day **nobody had opened a
clinic page in a browser**, which is the same reason it was not noticed and the reason it
was so cheap to miss.

### 5.4 The fix (`a58fa3f`)

`asset_helper_tenancy => false`. Confirmed safe before changing it: the only two mentions of
`tenant_asset` or the tenant-asset route in the entire codebase are the upstream comment and
the flag itself. Nothing in this app serves per-tenant files over HTTP, so tenancy on this
helper bought nothing and cost the frontend. `asset()` now emits the ordinary `/build/…`
path that Vite writes and the apex already served at `200`.

---

## 6. The production deploy — `main` at `536567f`

### 6.1 🔴 The box had no `.git`

"Deploy through the repo" turned out to be impossible as stated: `/var/www/africhart-emr`
was an **rsync'd copy**, not a checkout. A6 had shipped files, not commits, so there was no
`HEAD` to move and no way to know what the box actually ran.

It was converted to a real checkout — but **audited before anything was overwritten**:
`git init`, `git fetch`, then `git reset` (index only, working tree untouched) to diff the
server's files against the commit A6 had shipped. Among tracked code the only differences
were `artisan`'s mode bit, three stale doc copies, and `api-docs.json` with zero line
changes — **no hand-edited code**, so `git checkout -f` was safe, and was verified as safe
rather than assumed.

The audit also showed the reverse problem: **33 tracked files the rsync had never shipped
at all** — spatie's backup notification translations and the l5-swagger views — plus the
`storage/` `.gitignore` placeholders. The box had been running with files git believed were
deployed and which were simply absent. The checkout restored them; all are confirmed present
now. That is the concrete cost of shipping files instead of commits: nobody could have known,
because there was nothing to compare against.

### 6.2 The deploy

`php artisan down` → `git checkout -f -B main origin/main` → `composer install --no-dev
--optimize-autoloader` → `npm ci && npm run build` → `migrate` → **`tenants:migrate`** →
cache clears → `queue:restart` → `up`. A `mysqldump` safety net of central + both tenants
was taken first and verified by contents.

**`migrate` reported `Nothing to migrate`.** Both of this release's migrations are *tenant*
migrations, so the central command was correctly a no-op — and a release that stopped there
would have left both clinics without columns the new code selects. Verified by **reading the
schema of each tenant**, not by trusting the command:

```
alpha   gender, emergency_contact_name, emergency_contact_phone, emergency_contact_relationship  |  medication_id present
bravo   gender, emergency_contact_name, emergency_contact_phone, emergency_contact_relationship  |  medication_id present
```

### 6.3 The experience, proven in a browser

```
no /tenancy/assets/ requests            0 found
/build/assets/app-C9YJUjQb.css          200
/build/assets/app-Bgx0rOE9.js           200
CSS parsed AND applied                  body background = rgb(248, 247, 245)
window.Alpine                           defined — v3.15.12
console errors                          0
```

Point 3 is deliberate: asserting a *computed* style proves the CSS was applied, not merely
fetched. Point 4 is the check that caught the original defect.

Then the features themselves, confirmed **by the saved rows**:

- `Paracetamol` → `medication_id = 1`; edited away from the catalogue name → cleared to
  `NULL`; `Herbal tonic XYZ` → `NULL`. Catalogue-linked and free-text both work.
- Emergency contact and gender persisted.
- The audit trail, complete and distinguishable:

```
Checked in Isolation Probe A as #1, no doctor assigned yet     ← previously invisible
Recorded vitals for Isolation Probe A                          ← distinct from check-in
Issued invoice ALPH-INV-20260830-0001 for ₦8,000.00
Marked invoice ALPH-INV-20260830-0001 PAID — ₦8,000.00 by Cash
```

Zero errors in the production log since the deploy.

### 6.4 Two false alarms the deployer caught in its own work

- **"Production backups are failing."** Wrong. Per-tenant storage is `0700 www-data` and the
  scheduler runs as `www-data`; the 02:00 run had succeeded for both clinics. The failures
  were hand-runs as `wayne` — *the wrong user*, not a broken backup. Now §4 of the runbook.
- **"The server's CSS is suspect"** — 155 fewer selectors than the local build. They are
  Laravel's **debug exception-renderer** styles, which should not ship to production. Every
  class the app's own views use resolves identically in both builds.

Both were reported as findings first and corrected on checking. Recording them is the point:
the same discipline that caught the real defect also produced two false ones.

### 6.5 The durable fix — [`docs/DEPLOYMENT.md`](../docs/DEPLOYMENT.md) (`536567f`, 234 lines)

A one-line config fix does not stop this class of gap recurring; a required step does. §6 of
the runbook makes the browser check part of the deploy, with four assertions that must hold
on a clinic subdomain: no `/tenancy/assets/` requests, every asset `200`, a **computed**
style proving CSS applied, and `window.Alpine` defined.

It also records what this deploy cost time to learn: that `tenants:migrate` is a separate
command and a tenant-migration release is otherwise silently unapplied; that per-tenant
storage is `0700 www-data`; and the two harness traps (`networkidle` never settles on pages
that poll; several pages carry a hidden first submit button).

### 6.6 Verification artifacts removed (2026-08-31)

The browser walk-through left real rows in `alpha`. They were cleared: **24 rows** — 3
invoice items, 1 invoice, 2 prescriptions, 1 consultation, 2 queue entries, 2 patients and
13 audit rows — deleted children-first in one transaction behind a verified `mysqldump`, and
confirmed gone by reading both databases afterwards. `alpha` and `bravo` are now identical
in shape: 1 isolation probe, 10 catalogue drugs, 1 staff, 2 settings, 0 clinical rows.

The walk-through's admin password was re-randomised to a value recorded nowhere, with
`remember_token` cleared and all 20 sessions deleted — verified by `Hash::check()` against
the old value returning false, rather than by assuming the update applied. **A fresh invite
is required to sign into `alpha` again.**

---

## 7. Corrected SOW status

Phase A is unchanged from report #4 in substance — **and now experience-verified**, which it
was not before.

| | | |
|---|---|---|
| **A1** Tenancy architecture | ✅ | Complete 2026-08-25 |
| **A2** Per-tenant configuration | ✅ | Complete 2026-08-28. On-screen clinic identity remains a **B4** feature, not an A2 gap |
| **A3** VPS infrastructure | ✅ | Complete 2026-08-29 |
| **A4** Provisioning command | ✅ | Complete 2026-08-29 (`9e2e4b8`) |
| **A5** Backups | 🟡 | **Two gates left, unchanged and still client-blocked** (§9) |
| **A6** Tenant #1 | ✅ | Complete 2026-08-29 as the infrastructure milestone — **and as of this period, verified as a working experience, not only as working infrastructure** |

**Phase B:** **B3** ✅ · **B4** 🟡 — two seams shipped, Settings hub absent; **this period
produced its evidence** (§3.2 item 5) · **B7** 🟡 · **B1 · B2 · B5 · B6** ⬜.

---

## 8. Test suite

**65 tests, 337 assertions, all passing** — a real `composer test:tenancy` run against MySQL
while writing this report, 500.3 s.

**Unchanged from reports #3 and #4, and deliberately so.** Nothing this period is the kind
of defect a PHPUnit suite catches. A test asserting `asset()` returns a string would have
passed against the broken config; the defect was that the returned URL 500'd *in a browser*,
which is why the durable fix is a required browser step (§6.5) and not a new unit test.

The suite trap from report #4 still stands and is still unfixed in the script: run it as
`COMPOSER_PROCESS_TIMEOUT=0 composer test:tenancy`, or composer's 300 s process limit kills
a passing 500 s suite mid-run and strands test rows that fail the *next* run.

---

## 9. Open items and risks

### ⚠️ The two pre-real-clinic gates — client-blocked, unchanged

1. **Off-site backup destination** — Google Drive via `rclone`. **Blocked on the Client** for
   the backup Google account. *A lost VPS is still a lost backup.*
2. **Execute the restore drill for real** on an AES-encrypted archive.

Neither moved this period. Both still gate the first real clinic.

### ⚠️ The PHP/MySQL timezone skew — unchanged, still before real data

PHP runs UTC while the MySQL session runs `+02:00`. Nothing observed breaks today because
every timestamp in play is written by PHP, but a SQL-side default would disagree with an
application-written one by two hours in the same row — and the table where that matters most
is `audit_logs`, which this period made considerably more valuable. **An audit-trail
integrity issue.**

### ⚠️ The scheduler's outermost alarm is still unarmed

`/up` returns 500 on silence and 200 when healthy, proven on production. **Nothing polls
it.** The dead-man's switch is not connected. Unchanged since report #3.

### ⚠️ Get Started promises an email nobody sends — still B5

`POST /signup` writes one `marketing_leads` row and flashes a toast, while the page promises
three times to email login details. No route or view surfaces leads. Unchanged.

### ⚠️ Blocked on the Client, unchanged

The **nine Paystack architecture questions** in SOW §6 must be resolved before any billing
code is written.

### Deployment state

`main` at `536567f` live at `/var/www/africhart-emr`, **now a genuine git checkout** — the
box can be described by a commit hash for the first time. Two clinics, 20 tables each, zero
patients. Mail is `log`. No off-site backup.

---

## 10. Suggested sequence from here

1. **The two backup gates** — off-site via `rclone`, then the restore drill executed for
   real. Nothing should cross the real-data threshold until both close.
2. **The timezone skew** — cheap, and now more clearly an audit-trail correctness issue than
   it was a report ago.
3. **B4 — Settings hub and clinic identity on screen.** The evidence for it came out of §3
   of this report, and the groundwork exists.
4. **B1 + B2 — billing.** The nine Paystack questions **first**.
5. **B5 + B6 + B7** — super-admin (with the Get Started fixes) → telemetry → compliance.

**Then, and only then, a real first clinic.**

---

*Written 2026-08-31 against `536567f` on `main`. Production read live at `81.0.219.165`,
including a real browser session over the public internet — which is the one form of
verification this project had not previously used, and the one that found the defect this
report leads with.*
