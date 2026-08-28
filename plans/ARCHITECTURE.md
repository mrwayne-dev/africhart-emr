# AfriChart EMR — SaaS Architecture

**Status:** A1 **BUILT and accepted** (2026-08-25). Design locked 2026-08-23; §12 records where implementation corrected the design. Supersedes the earlier *SaaS Scaling Architecture & Roadmap*, which
was written before the VPS existed and has drifted (see §10).
**Written:** 2026-08-23 · **Revised:** 2026-08-25 · **Repo at:** `11872c0`
**Companions:** [`PHASE2_SOW_TODO.md`](PHASE2_SOW_TODO.md) · [`PHASE2_PROGRESS_2026-08-22.md`](PHASE2_PROGRESS_2026-08-22.md)

---

## 1. Locked decisions

These are settled. They are recorded here as decisions with their reasoning so they are not
re-litigated mid-build, and so the reasoning survives the person who made them.

| # | Decision | Rationale |
|---|---|---|
| **D1** | **`database` driver for cache, sessions AND queue** | Isolation is **structural**, not conventional. Each tenant's cache entries, sessions and queued jobs physically live in that tenant's own database, so a cross-tenant leak would require a connection bug, not merely a forgotten key prefix. Slower than Redis, and worth it: the isolation story is the product's commercial proposition, and "we cannot leak because the data is not in the same database" is a sentence a clinic owner can check. **⚠️ Implementation correction: the cache does NOT follow the connection swap on its own** — `CacheManager` resolves a store once and `DatabaseStore` holds a live `Connection` object. `App\Tenancy\DatabaseCacheTenancyBootstrapper` is REQUIRED to make this decision true. See §12. |
| **D2** | **`stancl/tenancy` in multi-database mode — one database per tenant** | Every clinic gets its own MySQL database. Worth stating explicitly not because the package fights it — v3's *published config* is already multi-database, with `DatabaseTenancyBootstrapper` active and a `MySQLDatabaseManager` wired — but because most of stancl's **tutorials and examples** demonstrate single-database tenancy with a `tenant_id` column. Anyone learning the package from its docs will reach for the wrong pattern; the config is right by default. |
| **D3** | **Redis deferred to Stage 4** | Not rejected — deferred. Revisit when measured load justifies it, at which point the tenancy bootstrappers' key-prefixing must be audited as a security change, not a performance one. |
| **D4** | **Central domains are excluded from tenant resolution** | `africhartemr.com`, `www.africhartemr.com`, `admin.africhartemr.com`. Everything else under `*.africhartemr.com` is a tenant. |
| **D5** | **Clinic staff live in a per-tenant `staff` table with a `Staff` model** | Renamed from `users`. Platform operators are a separate concept in the central `platform_admins` table. The two must never be the same table, the same model, or the same guard. |
| **D6** | **Pricing lives in the `plans` table — single source of truth** | Client-confirmed 2026-08-25: Starter ₦45k/mo + ₦75k setup · Clinic ₦85k/mo + ₦120k setup · Group ₦65k **per site**/mo + ₦150k setup. `price_basis` models per-site explicitly. The figures previously existed in four places; `/pricing`, Home and Sign-Up now all read the table. **Annual pricing is not confirmed — do not derive one.** See SOW to-do §0.1 |
| **D7** | **Clinics only — hospitals are out of scope** | Outpatient clinics and multi-clinic groups. No inpatient/ward tier; "Group" means several outpatient clinics under one owner, not a hospital with departments. A hospital tier is a possible future phase — **do not design speculatively for it**. See SOW to-do §0.2 |
| **D8** | **Paystack is the payment gateway; Wema Bank is the settlement account** | B1 builds against Paystack only — no second gateway, no abstraction for a provider we do not use. Wema is a Paystack dashboard setting: no bank integration, nothing to build. The gateway choice is settled; the nine architecture questions are not. See SOW to-do §0.3 |

---

## 2. The shape in one paragraph

One Laravel codebase. One **central** database holding the clinic registry, platform operators and
plans. One **tenant** database per clinic holding every clinical record that clinic owns. A request
to `grace.africhartemr.com` is resolved to a tenant by its subdomain, the default database
connection is swapped to that tenant's database for the life of the request, and swapped back
afterwards. A request to `africhartemr.com` or `admin.africhartemr.com` never enters tenant context
at all.

---

## 3. Domain model and routing

### 3.1 Central domains

| Host | Serves | Auth |
|---|---|---|
| `africhartemr.com` | Marketing site (built — B3), lead capture, find-your-clinic | none |
| `www.africhartemr.com` | Redirect to apex | none |
| `admin.africhartemr.com` | Super-admin panel (B5) | `admin` guard → `platform_admins` |

These are configured as stancl's **central domains** and are excluded from tenant identification.

### 3.2 Tenant domains

Every other `*.africhartemr.com` label is a tenant subdomain, resolved against
`clinics.subdomain`. An unrecognised subdomain returns a friendly "no such clinic" page — **not**
the current `444`, which is right for junk hostnames but wrong for a mistyped clinic name.

> **The wildcard certificate covers one label only.** `grace.africhartemr.com` ✅,
> `a.b.africhartemr.com` ❌. Fine for this design; it constrains any future per-clinic
> sub-subdomains.

### 3.3 Reserved subdomains

Clinic sign-up **must reject** these, because each either already resolves to central
infrastructure or will be needed. Enforced in validation at sign-up *and* at provisioning — the
second is what actually protects the system, since the first can be bypassed.

> ✅ **Done — Sprint 2, 2026-08-27.** The list was read by nothing for the whole of A1: a clinic
> claiming `api` provisioned cleanly, database and all, which was demonstrated before it was
> fixed. Enforcement now sits on the **`Clinic` model** (`saving`, so renames are caught too),
> not in a provisioning command — because `tenant:create` is not the only way a clinic is born.
> Seeders, tinker, the isolation suite and every future admin screen all go through
> `Clinic::create()`, so the guard belongs where they all pass. `App\Tenancy\Subdomain` is the
> single definition of what a valid address is; `App\Rules\UsableClinicSubdomain` applies it to
> the sign-up form. The reserved set is the configured list **plus** every label sitting in front
> of a central domain — derived for the same reason `central_domains` is derived from
> `root_domain`, since a static list beside a derived one is exactly what drifted before.

```
www, admin, app, api, mail, support, staff, help, status, blog
```

The list lives in config so it can grow without a migration. Add to it before you need to, not
after: reclaiming a subdomain from a live clinic means changing the address their staff have
bookmarked.

---

## 4. Database topology

### 4.1 Central database — `africhart_central`

| Table | Purpose |
|---|---|
| `clinics` | The registry. See schema below |
| `platform_admins` | Operators — us. Separate model, separate guard, never mixed with clinic staff |
| `plans` | Tier definitions and their feature maps (feeds B2 gating) |
| `marketing_leads` | Already built and already central. Demo, contact and sign-up submissions |
| `jobs`, `job_batches`, `failed_jobs` | **Central** queue — cross-tenant work, AND every tenant job: jobs are stored centrally with a `tenant_id` in the payload, so one worker serves all clinics |
| `sessions`, `cache`, `cache_locks` | **Central's own.** Not an oversight in reverse — the central app is a real app: the marketing site posts CSRF-protected forms and the admin panel authenticates. Under D1 those need a session table on the central connection. These tables exist in BOTH databases, and a central session is a different session from a tenant one — which is what §6.2 asserts |
| `scheduled_task_runs` | Every scheduled task run, so SILENCE is detectable (§7) |

**`clinics` schema:**

| Column | Type | Notes |
|---|---|---|
| `id` | string/uuid | stancl's tenant key |
| `name` | string | Clinic's display name |
| `subdomain` | string **unique** | The address. Validated against the reserved list |
| `tenancy_db_name` | string | Tenant DB name, derived at provisioning. **Named for stancl's internal-key convention** (`HasInternalKeys::internalPrefix()` is `tenancy_`, and `DatabaseConfig::getName()` reads exactly this attribute), not the `database` this doc first specified — same information, under the name the package contracts on |
| `status` | string | `provisioning · trialing · active · past_due · suspended · cancelled` |
| `plan` | string | FK to `plans` |
| `owner_name` | string | |
| `owner_email` | string | **The primary contact channel.** See note below |
| `owner_phone` | string | Support callback only |
| `trial_ends_at` | timestamp nullable | |
| `timestamps` | | |

> **`owner_email` is the system's primary comms channel.** Every transactional message —
> provisioning, invoices, dunning, password resets, trial expiry — goes to email. WhatsApp is a
> support convenience only and gets **no schema field**: modelling a channel we do not send on
> would imply a capability that does not exist.

### 4.2 Tenant database — one per clinic

Everything clinical, plus the clinic's own staff and its own session/cache/job tables.

| Table | Notes |
|---|---|
| `staff` | **Renamed from `users`.** The clinic's people. See §5 |
| `patients`, `consultations`, `prescriptions`, `invoices`, `invoice_items`, `patient_queue` | Clinical records |
| `medications` | The clinic's own drug catalogue at the clinic's own prices (A2) |
| `audit_logs` | Per-clinic. Always written, never gated — B2 gates *visibility*, never the trait |
| `sessions` | **Per D1.** This is what makes session isolation structural |
| `cache`, `cache_locks` | **Per D1** |
| `jobs`, `job_batches`, `failed_jobs` | **Per D1.** Tenant-scoped work only |
| `password_reset_tokens` | Per-tenant: a reset token is meaningless outside its clinic |
| `personal_access_tokens` | Sanctum. Per-tenant — see the warning in §8.3 |
| `settings` | New (A2). ID prefix, consultation fee, branding |

### 4.3 Migration split

`database/migrations/` splits into `database/migrations/central/` and
`database/migrations/tenant/`. **Twenty-four** migrations as built (nineteen at design
time, plus the five central tables the design did not anticipate — see §12):

| Set | Count | Contents |
|---|---|---|
| **Central** | 8 | 3 × `marketing_leads` · central framework tables (sessions, cache, jobs) · `plans` · `clinics` · `platform_admins` · `scheduled_task_runs` |
| **Tenant** | 16 | 3 Laravel scaffolding (`users`→`staff`, `cache`, `jobs`) + 13 clinical |

> The scaffolding migration `0001_01_01_000000_create_users_table.php` creates **three** tables in
> one file — `users`, `password_reset_tokens` and `sessions`. All three are tenant-side, so the
> file moves as a unit. Do not split it and leave `sessions` behind in central; that would
> reintroduce exactly the cross-tenant session risk D1 exists to prevent.

---

## 5. `users` → `staff` ✅ *(done — commit `43a413f`, 48 files, one atomic commit)*

The clinic's people are **staff of that clinic**, not users of a platform. With platform operators
arriving in central `platform_admins`, keeping both called "user" guarantees somebody eventually
authenticates one against the other.

This is a **full rename**, not a table alias:

| Layer | Change |
|---|---|
| Table | `users` → `staff` (tenant DB) |
| Model | `App\Models\User` → `App\Models\Staff` |
| Enum | `App\Enums\UserRole` → `App\Enums\StaffRole` |
| Factory / seeder | `UserFactory` → `StaffFactory`, `UserSeeder` → `StaffSeeder` |
| Auth provider | `config/auth.php` provider `users` → `staff`, `'model' => Staff::class` |
| Auth guard | `web` guard → provider `staff` |
| New guard | `admin` guard → provider `platform_admins` → `App\Models\PlatformAdmin` (central) |
| Password broker | Points at the tenant `password_reset_tokens` table |
| Foreign keys | **8** constraints → `constrained('staff')` |
| Relationships | **8** `belongsTo(User::class, …)` across six models |

**The 8 foreign keys** — 7 written as `constrained('users')`, plus `audit_logs.user_id` which uses
the implicit `constrained()` form and is easy to miss on a grep:

```
patients.registered_by          consultations.doctor_id
prescriptions.prescribed_by     invoices.created_by
patient_queue.checked_in_by     patient_queue.assigned_doctor_id
patient_queue.vitals_recorded_by
audit_logs.user_id              → implicit constrained(); rename column to staff_id
```

There is also **`sessions.user_id`**, which is an index rather than a foreign key. It holds a staff
id and is tenant-side; it needs renaming for consistency even though nothing will break if it is
missed.

**Relationship methods to update:** `AuditLog::user()`, `Invoice::creator()`,
`Consultation::doctor()`, `Patient::registeredBy()`, `PatientQueue::{checkedInBy,
assignedDoctor, vitalsRecordedBy}`, `Prescription::prescribedBy()`.

> A pleasing accident: `app/Http/Controllers/StaffController.php` already exists and already
> manages what it calls staff — it simply operates on the `User` model. The vocabulary was right
> before the schema was.

---

## 6. Isolation guarantees the A1 test suite must prove ✅ *(built — 28 tests, 143 assertions; `composer test:tenancy`)*

Four guarantees. Each is proved by a test that **attempts a cross-tenant leak and asserts it
fails** — not by a test that merely confirms the happy path works. A test that only shows tenant A
reading tenant A's data proves nothing about isolation.

Every test runs against **two real provisioned tenants**, not mocks.

### 6.1 Data isolation

> Given clinics A and B each holding patients, no query executed in A's context can return a record
> belonging to B.

- Seed A and B with distinguishable patients, consultations, invoices and audit logs.
- In A's context, assert `Patient::count()` equals A's count only.
- Attempt direct access by B's primary key from A's context; assert not found.
- Assert the two databases are physically distinct (`DB::connection()->getDatabaseName()` differs).

### 6.2 Session isolation

> A session established on tenant A is not valid on tenant B.

- **`SESSION_DOMAIN` must remain unset**, so the cookie is host-only. Setting it to
  `.africhartemr.com` shares one session cookie across every clinic subdomain and presents one
  authenticated session to all of them. For a system holding medical records that is a serious
  isolation failure, and it is a one-line mistake.
- **Test:** authenticate as staff on A, replay that session cookie against B, assert redirect to
  login rather than an authenticated response.
- **Test:** assert `config('session.domain')` is null — a guard that fails loudly the moment
  somebody "helpfully" sets it.

### 6.3 Cache isolation

> A cache key written in A's context is not readable in B's context.

- Write `Cache::put('probe', 'A-value')` in A; assert `Cache::get('probe')` in B is null.
- Assert the row lands in A's `cache` table and that B's `cache` table is empty — proving isolation
  by **location**, not by prefix.

### 6.4 Queued-job isolation

> A job dispatched in A's context executes against A's database, whichever worker picks it up.

- Dispatch a job in A's context that writes a record; run the worker; assert the record exists in
  A and not in B.
- Dispatch from A and B, process both, assert neither wrote into the other.
- Assert a job serialised in A's context restores A's tenant on unserialise — the classic failure
  is a job that runs against whatever connection the worker last had.

---

## 7. Scheduler under tenancy ✅ *(built — commit `d626a5e`)*

The current cron runs `schedule:run` **once, against one database**. Under tenancy that silently
backs up whichever database the default connection points at — a failure mode that looks exactly
like success, which is how backups came to have never run at all before Sprint 0.

- Per-tenant work (backups) iterates tenants explicitly.
- Cross-tenant work (trial expiry, dunning) runs **centrally, once**.
- `withoutOverlapping()` — a backup across N tenant databases will outlive its minute.
- Alert on **silence**, not only on failure. A cron that stops firing produces no error.

---

## 8. What these decisions force to change in the existing codebase

*Written as a forecast against `034c894`. Kept as written, because the estimate proved
accurate and its accuracy is itself worth recording — but §8.1–8.3 are now **DONE**
(commit `43a413f`) and §8.4–8.5 are **still outstanding**. The one thing the forecast
missed is called out under 8.1.*

### 8.1 The rename surface

**22 files** reference the `User` model: 6 models, 6 controllers, 2 policies, 2 services,
3 seeders, 1 factory, `config/auth.php`, `AppServiceProvider`. Plus 8 FK constraints and 8
relationship methods (§5). This is mechanical but wide, and it must be done in one commit — a
half-renamed auth layer is worse than either state.

> ✅ **Done** — 48 files in one commit. The 22-file count was right about the model
> references and the 8 FKs were exactly right, including the implicit `constrained()` on
> `audit_logs` this doc warned a grep would miss.
>
> ⚠️ **What the forecast missed:** `UserResource`. It referenced the model by NAME only —
> no import, no `User::` call — so nothing that greps for the model could ever have found
> it. Renames leak through vocabulary, not just imports. Also missed: `sessions.user_id`
> must NOT be renamed, because Laravel hardcodes that column name in
> `DatabaseSessionHandler::addUserInformation()`.

### 8.2 Session, cache and job tables move to tenant

`SESSION_DRIVER=database`, `CACHE_STORE=database` and `QUEUE_CONNECTION=database` are already the
deployed configuration, which is what makes D1 cheap. But the tables currently live in the single
database and must be created per tenant by the tenant migration set.

> ✅ **Done**, with a correction: they had to be created in **both** sets, not moved. See
> §12 item 2. And the cache needed a bootstrapper of its own to follow the connection at
> all — §12 item 1, the bug the acceptance gate caught.

### 8.3 ⚠️ Sanctum tokens will break on rename ✅ *(handled — morph map, same commit)*

`personal_access_tokens` uses `morphs('tokenable')`, which stores the **fully-qualified class
name** — currently the string `App\Models\User` — in `tokenable_type`. Renaming the model
invalidates every existing API token, silently: the token row remains, the morph target no longer
resolves.

Two API consumers exist today (`/api/v1/*`, Sanctum-authenticated). Options: a data migration
rewriting `tokenable_type`, or a `Relation::enforceMorphMap()` alias. **Recommendation: morph map.**
It fixes it permanently and stops the class name being a database value at all — which is the
underlying defect, and it will bite again at the next rename otherwise.

### 8.4 Items A2 will move — ⬜ STILL OUTSTANDING

None of these belong to A1, but all become **wrong** the moment a second clinic exists:

| Item | Where | Becomes |
|---|---|---|
| `ACH-` ID prefix | `PatientService.php:115`, `ConsultationService.php:87`, `InvoiceService.php:167` | Per-tenant setting. Two clinics would otherwise mint identical patient IDs |
| Consultation fee | `config/billing.php:14`, read at `InvoiceService.php:44` | Per-tenant setting |
| Invite codes | `config/registration.php:16-20`, four `.env` values, one per role, never expiring | Per-clinic, single-use, expiring invite records |
| Drug catalogue | `medications` table | Already DB-backed; needs tenant scoping |

> The invite codes are the urgent one. They are **global and shared across the entire install**:
> today one code admits an admin to the only clinic. With two clinics and no change, the same code
> admits an admin to *whichever clinic they happen to visit*. That is a cross-tenant
> authentication hole, and it must close before tenant #2 — not before tenant #10.

### 8.5 Marketing sign-up already feeds this — ✅ blocklist NOW ENFORCED

`marketing_leads` (central, built) collects clinic name, owner name, email, phone, city and chosen
plan — the exact inputs `tenant:create` needs. The sign-up form already previews the subdomain from
the clinic name. **The reserved-subdomain blocklist (§3.3) must be added to that form's
validation**, since it currently previews any name at all.

> ✅ **Done.** `UsableClinicSubdomain` validates the address the entered NAME would produce, since
> the form has no subdomain field of its own. It deliberately does **not** check availability:
> telling a public form that a subdomain is taken confirms a clinic exists at that name, which is
> precisely the enumeration find-your-clinic refuses to do. The reserved list leaks nothing —
> it is static config, identical for every visitor. Uniqueness stays with the unique index at
> provisioning, where an operator can actually resolve a collision.
>
> The preview and the server derive the address in two languages that cannot share code, so a
> test executes the view's **real JavaScript in node** and compares it to the PHP for the same
> inputs. That found a shared bug: truncating at 40 characters can cut on a hyphen, leaving a
> trailing one that the server would then reject as malformed — a long, legitimate clinic name
> failing with a message about letters and numbers. Both sides now trim after truncating.

---

## 9. Provisioning (A4) — shape only

`php artisan tenant:create` must be **idempotent with rollback on partial failure**. The failure
that matters: a clinic row created, database created, migrations half-run, command dies. The clinic
then exists in the registry pointing at a broken database, and the operator cannot re-run because
the subdomain is taken. Every step must be undoable, and the command must be safe to re-run after
any failure.

---

## 10. What drifted in the previous architecture doc

| It said | Reality |
|---|---|
| Stage 0: "Single client, live and working" | **Not launched.** No clinics signed, no real patient data |
| Stage 1: "Migrate the existing client to tenant #1" | **Not applicable** — nothing to migrate. A6 is a fresh stand-up |
| Stage 1: VPS stand-up | **~90% done.** Only off-site object storage outstanding |
| Stage 3: monitoring, rate-limited auth, backup encryption | **All done early**, out of sequence |
| §4: Redis for cache/sessions/queue | **Superseded by D1.** `database`, with Redis deferred to Stage 4 |
| §7: "deployment becomes a real pipeline" | Scripted 2026-08-22; runs green |

---

## 11. Open items, owned elsewhere

- **Contabo Object Storage credentials** *(Client)* — backups remain local to the box. A lost VPS
  is a lost backup. Highest-risk open item and it needs no engineering.
- **Paystack architecture** *(joint)* — nine questions at §6 of the to-do. Blocks B1.
- **SOW Appendix 1 pricing** *(Client)* — blank, while `/pricing` publishes the platform-spec
  proposal publicly.

---



---

## 12. Where implementation corrected the design

The design above held. Five things it did not anticipate, each found by building or by
the acceptance gate rather than by review — recorded so the doc stays trustworthy and
so the corrections are not silently absorbed.

| # | The design said | Reality | Consequence |
|---|---|---|---|
| 1 | D1: cache isolation is structural because the tenant connection swaps | **False as written.** `CacheManager` resolves a store once; `DatabaseStore` holds a live `Connection` OBJECT. Every tenant's cache read and write went to `africhart_central` | `DatabaseCacheTenancyBootstrapper` added. Found by §6.3, not by review — it had been committed and would have shipped |
| 2 | §4.1 lists central's tables | Omitted `sessions`, `cache`, `cache_locks`. The central app posts CSRF-protected forms, so under D1 it needs its own session table | Central framework migration added. These tables exist in both databases, which is correct rather than duplication |
| 3 | §4.1 names the column `database` | stancl reads `tenancy_db_name` — its internal-key convention | Column renamed to match the package's contract |
| 4 | §3.1 lists central domains | They were a hardcoded list beside `root_domain` and drifted the moment it changed, leaving every tenant route unreachable | `central_domains` is now DERIVED from `root_domain` |
| 5 | D2's rationale: stancl defaults to single-DB | v3's *published config* is already multi-database. What misleads is its **tutorials**, which demonstrate single-DB with a `tenant_id` column | Decision unchanged; rationale corrected |

### Two traps worth carrying into A2/A4

- **`Job::dispatch()` pushes in its DESTRUCTOR.** `fn () => Job::dispatch(...)` hands the
  pending job out of a tenant-scoped closure, so it is built after tenancy ends, with no
  tenant. Any code shaped `return SomeJob::dispatch(...)` inside a tenant callback has
  this bug. Pinned as its own test.
- **spatie's `backup:run` takes its config by constructor injection.** A runtime
  `config()` override is invisible unless `--config` is passed — which is how the
  per-tenant backup command archived the central database twice while reporting success.

### Still outstanding from this document

- ~~**§3.3 / §8.5 — the reserved-subdomain blocklist is enforced NOWHERE.**~~ ✅ **Closed
  Sprint 2, 2026-08-27.** Enforced on the `Clinic` model and in sign-up validation; 10 tests,
  including a revert-to-red run where removing the guard let `www` be provisioned and let an
  existing clinic be renamed onto `api`.
- **§8.4 — the A2 items are untouched**, and the invite codes among them are a
  cross-tenant authentication hole the moment a second clinic exists.
- **§9 — `tenant:create` is not built.** The create+migrate pipeline it wraps already
  runs and is exercised by every isolation test.

---

---

*Written 2026-08-23, revised 2026-08-25 against `11872c0`. Every codebase figure was read
from the repo or a live run, not estimated. §12 records where building it corrected the
design.*
