# Phase 2 SaaS — Scope-of-Work To-Do

**Source:** SCOPE OF WORK — AfriChart EMR SaaS Platform (Phase 2), Ref `ACT-DEV-006`
**Architecture:** *AfriChart EMR — SaaS Scaling Architecture & Roadmap* (see [§7 reconciliation](#7-architecture-doc--what-is-now-out-of-date) — parts of it are now out of date)
**Updated:** 2026-08-29 · **Phase A infrastructure COMPLETE** — Sprint 0 · B3 · A1 · A2 · A3 · A4 · A6 done; A5 has two gates left
**Latest report:** [`PHASE2_PROGRESS_2026-08-29.md`](PHASE2_PROGRESS_2026-08-29.md)
**Companions:** `plans/africhart-platform-spec-public-ui-plans.md` · `~/Documents/wayne/vps/wayneVPS-SETUP.md` · `~/Documents/wayne/vps/africhart-smoke-deploy.md`

Legend: ✅ done · 🟡 partial · ⬜ not started · ⚠️ decision needed

---

## Settled facts (previously open questions)

| Question | Answer |
|---|---|
| Is wayneVPS the Contabo box in the SOW? | **Yes — confirmed.** `81.0.219.165`, hostname `vmi3509781` |
| Who holds the Contabo + domain accounts (§7.2)? | **The Client holds them** — matches the SOW recommendation |
| Migrate the existing live clinic (A6)? | **No migration.** The EMR has not launched and no clinics are signed. A6 becomes *stand up Tenant #1 fresh* |
| Root-domain conflict (marketing vs EMR)? | **Deferred** — root domain gets reassigned at project end |
| Paystack architecture | ✅ **LOCKED 2026-08-29.** Gateway (D8) and all nine design questions (D9–D17) settled. See [§6](#6-paystack--architecture-locked-2026-08-29) and ARCHITECTURE §1.1. B1 is unblocked on design |
| **Pricing** | **LOCKED 2026-08-25.** Starter ₦45k/mo + ₦75k setup · Clinic ₦85k/mo + ₦120k setup · Group ₦65k **per site**/mo + ₦150k setup. Supersedes the platform-spec proposal; SOW Appendix 1 is answered |
| **Market** | **Clinics only.** Hospitals explicitly out of scope |
| **Payment gateway** | **Paystack (locked)**, settling to **Wema Bank** |

> **The A6 change matters more than it looks.** With no live clinic and no patient
> data, the data-protection gate is no longer blocking Phase A. It moves to
> *before first real patient*, not *before migration*. That removes the single
> heaviest dependency from the Phase A critical path.

---

## 0. Locked business decisions — do not re-open

Recorded 2026-08-25. These are commercial decisions from the Client, not
engineering preferences, and they are settled. A future sprint that finds them
inconvenient should raise it with the Client rather than quietly diverge.

### 0.1 Pricing — LOCKED

| Tier | Monthly | One-time setup |
|---|---|---|
| **Starter** | ₦45,000 | ₦75,000 |
| **Clinic** | ₦85,000 | ₦120,000 |
| **Group** | ₦65,000 **per site** | ₦150,000 |

- **Group is per-site**, modelled explicitly as `plans.price_basis = 'per_site'`. Two
  locations is ₦130,000/month, and the pricing page computes that rather than stating it.
- **The monthly subscription and the setup fee stay two distinct values.** One recurs,
  one does not; collapsing them into a single "cost" would misrepresent both.
- **These live in the `plans` table, which is now the single source of truth.** They
  previously existed in four places. `/pricing`, the Home teaser and both Sign-Up
  surfaces all read from the table — proven by changing a price in the database alone
  and watching the page follow.
- To change a price: edit `PlanSeeder` and re-seed. Nowhere else.
- ⚠️ **Annual pricing is NOT confirmed.** There is deliberately no annual column and no
  toggle. Do not derive one from the monthly figure — an annual price normally carries a
  discount, and inventing that discount would be inventing a commercial term.

### 0.2 Market — clinics only, hospitals out of scope

The product serves **outpatient clinics and multi-clinic groups**. There is no
hospital or inpatient tier, and "Group" means several outpatient clinics under one
owner — not a hospital with departments.

A true hospital tier (wards, admissions, departmental modules) is a **possible future
phase, not current scope**. Nothing in Phase 2 should be designed speculatively to
accommodate it.

### 0.3 Payments — Paystack, settling to Wema Bank

- **Paystack is the payment gateway.** It runs the recurring subscription billing in B1.
  Billing is built against Paystack only — no second gateway, no abstraction layer for
  a provider we are not using.
- **Wema Bank is the settlement account** where collected funds land. That is a setting
  inside the Paystack dashboard: **no integration work, no bank API, nothing to build.**
- ✅ The **gateway choice** and the **nine architecture questions** are both settled
  (2026-08-29). Subscription mechanism, setup-fee handling, trial mechanics, per-site
  metering for Group, read-only propagation and refunds are decisions **D9–D17** in
  ARCHITECTURE §1.1, indexed at [§6](#6-paystack--architecture-locked-2026-08-29).
  **They are not to be re-opened during the B1 build.**

> Remember the two Paystack accounts never touch: **patient → clinic** payments settle
> into *the clinic's own* merchant account, while **clinic → AfriChart** subscriptions
> settle into ours (Wema). Mixing them would put AfriChart in possession of clinical
> revenue, which is a regulatory problem nobody wants.

---

## 1. Scope map at a glance

| SOW item | | Status | Where it stands |
|---|---|---|---|
| **A1** | Tenancy architecture `[NEW]` | ✅ | **COMPLETE 2026-08-25.** Built and through the acceptance gate — 28 tests, 143 assertions, two real tenants |
| **A2** | Per-tenant configuration | ✅ | **COMPLETE 2026-08-28.** ID prefix, consultation fee, catalogue scoping, clinic identity on documents, and the invite-code gate all closed. Only the brand-string sweep remains, which is a B4 feature, not a gate |
| **A3** | VPS infrastructure `[NEW]` | ✅ | **COMPLETE 2026-08-29.** Stack stood up and proven by the A6 cutover: nginx wildcard vhost, wildcard TLS, PHP-FPM, MySQL with a least-privilege tenancy user, Redis, Supervisor, cron. Off-site object storage is **deferred by decision**, not outstanding work — it is now an A5 gate |
| **A4** | Provisioning command `[NEW]` | ✅ | **COMPLETE 2026-08-29** (`9e2e4b8`). `tenant:create` — preflight, blocklist, starter data with no demo fixtures, first-admin invite. **Rollback proven** by forcing a mid-provision migration failure and confirming by contents that nothing was left behind |
| **A5** | Backups | 🟡 | Per-clinic backup, cleanup and monitoring built and verified by contents, on the production box as well as locally; silence detection wired through `/up`. **Two gates left — now PRE-REAL-CLINIC, not pre-A6**, since A6 completed on throwaway clinics: run the restore drill for real, and wire the off-site destination |
| **A6** | Tenant #1 | ✅ | **COMPLETE 2026-08-29** — infrastructure milestone. `main` deployed to `/var/www/africhart-emr`, two throwaway clinics (`alpha`, `bravo`) provisioned through the real `tenant:create`, **isolation proven on the public `:443`**, smoke deploy torn down behind a verified dump. ⚠️ Onboarding a **real** first clinic is separate and still gated on the two A5 items |
| **B1** | Subscription & billing `[NEW]` | ⬜ | **Design locked (D9–D17).** Nothing built yet |
| **B2** | Plans, gating & metering `[NEW]` | ⬜ | Tiers designed, nothing built |
| **B3** | Public marketing site `[NEW]` | ✅ | **Complete 2026-08-22** — 10 pages + 3 legal docs. See `PHASE2_PROGRESS_2026-08-22.md` |
| **B4** | In-clinic account surfaces `[NEW]` | ✅🟡 | **Buildable scope COMPLETE 2026-09-04.** Settings hub, Clinic Profile, Team & Seats, Drug Catalogue, Branding, setup wizard, clinic identity. Three billing-dependent screens deferred to B1 |
| **B5** | Super-admin panel `[NEW]` | ⬜ | **Unblocked.** `platform_admins` + the `admin` guard now exist |
| **B6** | Product telemetry `[NEW]` | ⬜ | Not started |
| **B7** | Compliance | 🟡 | Legal docs written 2026-08-22 (pending review). **The data-isolation guarantee is now DEMONSTRABLE** — the §6 suite is the evidence. Breach plan not written |

---

## 2. ✅ SPRINT 0 — COMPLETE (2026-08-21)

Everything below was applied and **verified by live test**, not by reading config.

### 2.1 Three repo defects fixed — commit `10721b2`, branch `fix/sprint-0-hardening`

**1. Blank `BACKUP_NOTIFICATION_EMAIL` took the whole app down.**
`env()` returns `''` (not `null`) for a present-but-blank variable, so the fallback
chain in `config/backup.php` never fired. spatie validates the address at **boot**,
so `BACKUP_NOTIFICATION_EMAIL=` — exactly what `.env.example` shipped — killed every
artisan command and every HTTP request with `is not a valid email address`, naming no
variable. Fixed with a `?:` chain plus a real default in `.env.example`.
*Verified: blanked the value on the server; app booted normally.*

**2. `config:cache` could never run.**
`config/l5-swagger.php` instantiated a `ReflectionAnalyser` into the config array;
`config:cache` serialises via `var_export()`, which needs `__set_state()`.

> My first attempt at this was wrong and is worth recording: moving the object into
> `AppServiceProvider::register()` **did not help**, because `config:cache` boots the
> application before serialising and captured the injected object anyway. The working
> fix skips injection when `argv[1]` is `config:cache` or `optimize`, and re-injects on
> every normal boot — so the cached file holds `null` while the running app always has
> a real analyser.

*Verified: `config:cache` succeeds; cached file contains 0 occurrences of
`ReflectionAnalyser`; runtime `config()` still returns the live object.*

**3. `trustProxies(at: '*')` let any client forge its own audit-log IP.**
Not theoretical — **demonstrated**. A request carrying `X-Forwarded-For: 203.0.113.99`
wrote `203.0.113.99` into `audit_logs.ip_address`. For a system whose selling point is
the audit trail, that is an integrity defect. Now trusts only `TRUSTED_PROXIES`
(loopback by default).
*Verified: identical request after the fix recorded the real client IP; the poisoned
row from before sits next to it as evidence.*

**Also:** `.env.example` corrected for Laravel 13 — `CACHE_DRIVER` → `CACHE_STORE`,
`BROADCAST_DRIVER` → `BROADCAST_CONNECTION`, `MAIL_ENCRYPTION` → `MAIL_SCHEME`. The old
spellings were silently ignored.

### 2.2 Task scheduler — was never wired, now live

`/etc/cron.d/africhart-scheduler` runs `schedule:run` every minute as `www-data`.

> **Before this, nothing in `routes/console.php` had ever executed.** No `schedule:run`
> cron existed anywhere on the box — not in `/etc/cron.d`, not in any user crontab. The
> backup destination directory did not exist, because `backup:run` had never once fired.
> "Automated backups", which SOW §2 lists as a **completed pre-condition**, was
> code-complete and operationally dead.

*Verified: syslog shows CRON invoking it each minute; the log reads
`No scheduled commands are ready to run.`* Log rotation added
(`/etc/logrotate.d/africhart`) — it would otherwise grow ~50 KB/day.

### 2.3 Backups — first one ever taken, encrypted, restore rehearsed

- `BACKUP_ARCHIVE_PASSWORD` generated → archives are **AES-256** encrypted
- First backup produced: `2026-08-21-15-04-52.zip`
- **Restore rehearsed end-to-end** into a scratch database

| Table | Live | Restored | Match |
|---|---|---|---|
| users | 4 | 4 | yes |
| patients | 28 | 28 | yes |
| consultations | 12 | 12 | yes |
| prescriptions | 12 | 12 | yes |
| invoices | 9 | 9 | yes |
| invoice_items | 18 | 18 | yes |
| patient_queue | 4 | 4 | yes |
| medications | 10 | 10 | yes |
| audit_logs | 21 | 21 | yes |

Content spot-checked (users, invoice totals and status all intact); scratch DB dropped.

> **A false positive worth recording.** The first encryption check "passed" only because
> `unzip` was not installed — the extract failed for the wrong reason. After installing
> `unzip` and `p7zip-full`, `7z l -slt` confirms `Encrypted = +`,
> `Method = AES-256 Deflate:Maximum`, and an empty password is genuinely rejected.
> **Never accept a security check that passes because a tool is missing.**

### 2.4 PHP-FPM sized against measured reality

Measured **44 MB per worker** with 6.9 GB available, then budgeted ~1.8 GB:

| Setting | Was | Now |
|---|---|---|
| `pm.max_children` | 5 (Ubuntu default) | **40** |
| `pm.start_servers` | 2 | 6 |
| `pm.min_spare_servers` | 1 | 4 |
| `pm.max_spare_servers` | 3 | 12 |
| `pm.max_requests` | unset | 500 (guards leaks) |

### 2.5 Wildcard subdomain routing — see [§3](#3-wildcard-subdomain-routing--full-analysis)

### 2.6 Sprint 0 items NOT done, and why

- **Off-site backup storage** — **DEFERRED, not dropped, and no longer client-blocked.**
  Contabo Object Storage credentials were never issued; the plan is now a **free
  destination (Google Drive via `rclone`)**, which removes the dependency on the Client
  entirely. Backups remain **local to the box**, so a lost VPS is a lost backup.
  Acceptable only while there is no real patient data — this is a **pre-A6 gate**,
  tracked under A5.

---

## 3. Wildcard subdomain routing — full analysis

Asked for a proper look. It was in a worse state than "not done": it **appeared to work
by accident**.

| Layer | State before | State now |
|---|---|---|
| **DNS** (`*.africhartemr.com`) | ✅ Already correct — a Cloudflare wildcard record was already in place; `clinica`, `testclinic` and `randomxyz123` all resolved to `81.0.219.165` | unchanged |
| **TLS** | ✅ Cert SAN is `DNS:*.africhartemr.com, DNS:africhartemr.com` | unchanged |
| **nginx `server_name`** | ❌ Only `africhartemr.com www.africhartemr.com` | ✅ `*.africhartemr.com` added on both :80 and :443 |
| **Unknown-host behaviour** | ⚠️ Served the app | ✅ Connection closed (`444`) |

### The problem that was hiding

Requests to `clinica.africhartemr.com` were being answered — with a **302 and a valid
certificate** — despite no server block declaring that name. nginx was falling back to
the **first** `443` block, which happened to be the africhartemr.com one, because
nothing on 443 was marked `default_server`.

That meant two things:

1. **Any hostname pointed at this IP was served the application.** Host-header
   confusion, and an open door for someone parking a domain at the box.
2. **It was fragile in a way that would have bitten during Phase B.** The moment a
   second 443 block is added — the marketing site on the root domain — the fall-through
   target changes and tenant subdomain routing silently reroutes or breaks.

Now explicit: the app block names the wildcard, and a `default_server` catch-all
returns `444` for anything else.

*Verified:* `africhartemr.com`, `www.`, `clinica.`, `anything123.` → all `302`;
`unknown-host.example.com` → connection closed; `http://clinica.…` → `301` to HTTPS.

### ⚠️ The wildcard-routing trap for A1 — session cookie scope

Not in the architecture doc, and it will cause a **cross-tenant security bug** if missed:

> If `SESSION_DOMAIN` is ever set to `.africhartemr.com`, the session cookie is shared
> across **every** clinic subdomain. One authenticated session would be presented to
> every tenant. For a medical system that is a serious isolation failure.
>
> `SESSION_DOMAIN` must stay **unset** so cookies are host-only, and tenant session
> isolation must be an explicit test in the A1 isolation suite.

Also note a wildcard certificate covers **one label only** —
`clinica.africhartemr.com` ✅ but `a.b.africhartemr.com` ❌. Fine for the current
design; constrains any future per-clinic sub-subdomains.

- [x] ~~Add "session does not cross subdomains" to the A1 isolation tests~~ — specified in
      [`ARCHITECTURE.md`](ARCHITECTURE.md) §6.2, including an assertion that `SESSION_DOMAIN`
      is unset. Still to be *written*, but no longer at risk of being forgotten
- [x] ~~Decide unknown-subdomain UX~~ — **settled** in [`ARCHITECTURE.md`](ARCHITECTURE.md) §3.2:
      `444` stays for junk hostnames; an unrecognised *clinic* subdomain gets a friendly
      "no such clinic" page

---

## 4. Task scheduler — full analysis

### What was wrong

`schedule:run` did not exist on the box in any form. `routes/console.php` defines three
jobs (`backup:clean` 01:30, `backup:run` 02:00, `backup:monitor` 03:00) and **none had
ever executed**. The absence was invisible: no error, no log, no alert — the backup
destination directory simply never came into existence.

### What is running now

```
* * * * * www-data cd /var/www/africhart-smoke && php artisan schedule:run >> /var/log/africhart-scheduler.log 2>&1
```

`www-data` deliberately — it matches PHP-FPM, so nothing the scheduler writes into
`storage/` breaks web-request ownership.

### ⚠️ It must be redesigned for multi-tenancy in A1/A5

The current cron runs the scheduler **once, against one database**. Under tenancy that
silently backs up only whichever database the default connection points at — a failure
mode that looks exactly like success.

- [x] ~~Move scheduled work to per-tenant execution~~ **Done** — `tenants:backup`
- [x] ~~Per-tenant backups, each verifiable independently~~ **Done**, one archive per clinic
- [x] ~~Cross-tenant scheduled jobs run centrally~~ **Done** — `clinics:audit-trials`
      (deliberately report-only; the lifecycle transition is B1's)
- [x] ~~Add `withoutOverlapping()`~~ **Done**, verified present on all four tasks
- [x] ~~Alert on scheduler *silence*~~ **Done** — every run records a row and
      `schedule:audit` asserts a positive: each task succeeded recently AND every active
      clinic has a backup within 26h. Exits non-zero and fails `/up` when silent
- [ ] ⚠️ **Arm the external monitor on `/up`** — a silence detector that is itself silent
      detects nothing. Catching a *fully stopped* scheduler needs something outside the
      process polling the health endpoint. **Go-live deployment task**
- [ ] Repoint the cron path when the real app root replaces `/var/www/africhart-smoke`

---

## 5. To-do — remaining work

### PHASE A

#### A1. Tenancy architecture `[NEW]` ✅ — COMPLETE 2026-08-25
- [x] **Architecture doc** → [`ARCHITECTURE.md`](ARCHITECTURE.md), five decisions locked
- [x] `stancl/tenancy` v3.10.1 in **multi-database** mode, confirmed from the booted app
- [x] Central DB: `clinics` registry + `platform_admins` + `plans`
- [x] Subdomain identification against `clinics.subdomain` + per-request connection switching
- [x] Migrations split central (8) / tenant (16); mutually exclusive by construction
- [x] Per-tenant DB creation + migration (the `TenantCreated` pipeline)
- [x] Cache/session/queue drivers settled as `database` (D1)
- [x] **`users` → `staff` rename** — 48 files, one atomic commit, Sanctum morph map included
- [x] **Isolation test suite** — 28 tests, 143 assertions, real MySQL, two real provisioned
      tenants. Data · session · cache · queued-job, each proved by attempting a leak.
      Includes the `SESSION_DOMAIN`-is-null guard. Run: `composer test:tenancy`
- [x] Suite **sabotage-tested** — removing the cache bootstrapper, setting SESSION_DOMAIN,
      and sharing one database between two clinics each make it fail as required

> **The gate earned its keep.** It found that the cache was never isolated — every
> tenant's reads and writes went to `africhart_central`. Two further real bugs came out
> of the same work: per-tenant backups that archived the central database twice while
> reporting success, and `central_domains` drifting from `root_domain`. See
> [`PHASE2_PROGRESS_2026-08-25.md`](PHASE2_PROGRESS_2026-08-25.md) §4.

#### A2. Per-tenant configuration ✅ — COMPLETE 2026-08-28
- [x] ✅ **ID prefix — collision gate CLOSED.** Was `ACH-` hardcoded in three services while
      the sequence counter was per-clinic, so two clinics minted identical identifiers.
      Before: Hope and Grace both generated `ACH-20260828-0001` / `ACH-C-…` / `ACH-INV-…`.
      After: `HOPE-20260828-0001` and `GRAC-20260828-0001`.
      Lives on **central `clinics.id_prefix` behind a UNIQUE index**, not in tenant settings
      (a deviation from ARCHITECTURE §4.2, recorded as §12 item 6): distinctness across
      clinics cannot be enforced from inside one clinic's database
- [x] ✅ **Consultation fee** — tenant `settings.consultation_fee`. The old
      `config/billing.php` value is retained as the FALLBACK, so a clinic that has not set
      a fee bills the platform default rather than issuing a free consultation
- [x] ✅ **Drug catalogue — was already correct.** `medications` has been in the tenant
      migration set all along and is absent from central; each clinic already had its own
      list at its own prices. Verified against both live databases and now pinned by a test
      rather than left as a checklist claim
- [x] ✅ **Clinic identity on patient-facing documents.** The invoice PDF carried no clinic
      name at all — it was branded "AfriChart EMR", on the document a patient may hand to an
      HMO. It now leads with the issuing clinic's name, address and phone, with AfriChart
      demoted to the "generated by" footer credit. `EmailVerificationCode` and
      `AdminActivity` now name the clinic too (stamped in `AdminNotifier::send()`, the one
      funnel all six call sites pass through, rather than at six call sites).
      Untouched by design: the app shell, sidebar and API title still say AfriChart —
      AfriChart is the vendor. Per-clinic logos remain a B4 feature
- [x] ✅ **Invite codes — GATE CLOSED (Sprint 2, 2026-08-27).** The four global
      `REGISTER_CODE_*` values and `/register` are **deleted**, not disabled.
      Staff now join through `staff_invitations` in the **tenant** database:
      admin-issued, single-use, 7-day expiry, revocable, token stored only as a
      SHA-256 hash. Two holes closed, not one — the codes were global, *and* the
      old form let the visitor pick their own role, so whoever held the admin
      code chose to be an admin. Role now comes from the invitation record and
      is never read from the request.
      Cross-tenant rejection is **structural**: clinic A's invitation row does
      not exist in clinic B's database, so there is no ownership check to get
      wrong. Proved by `tests/Tenancy/InviteIsolationTest.php` (13 tests) —
      including a revert-to-red run with the table moved to central, where B
      duly accepted A's invitation (302, not 404).
      ⚠️ Bootstrap: a clinic's FIRST admin cannot come from an invitation.
      `php artisan clinic:invite` issues it from the CLI; **A4's `tenant:create`
      should call this at the end of provisioning**
- [ ] 🔶 **Brand-string sweep — EXAMINED 2026-08-27, genuine but NOT a security gate.**
      (The old pointer to "architecture doc §7" was stale — §7 is now *Scheduler under
      tenancy*. The referent is the platform spec's claim that branding is "already
      config-driven", which under tenancy means ONE config for every clinic.)
      Swept every clinic-facing surface and classified what it emits:
      - **Product branding — correctly global, no action.** `layouts/app`, `layouts/guest`,
        the sidebar logo and the API title all say "AfriChart". AfriChart is the vendor;
        naming itself in the product it sells is right. Per-clinic logos in the topbar are
        a *feature* (B4 Settings hub / spec §12), not a gate
      - **Identifier prefixes — THE REAL ITEM.** `ACH-` is hardcoded in three services and
        the counter is per-tenant, so two clinics mint the SAME strings. Verified live
        against both dev tenants on the same day — Hope (7 patients) and Grace (3) both
        generate `ACH-20260828-0001`, `ACH-C-20260828-0001`, `ACH-INV-20260828-0001`
      - **Clinic identity missing where a patient looks for it.** `invoices/pdf.blade.php`
        carries NO clinic name, address or phone anywhere — it is branded "AfriChart EMR"
        and never says which clinic issued it. That is the document a patient may hand to
        an HMO. `EmailVerificationCode` and `AdminActivity` likewise name only AfriChart
        (`StaffInvited`, built in Sprint 2, already names the clinic — the pattern to copy)
      **No cross-tenant leak exists**: every brand string is a literal or global config, so
      nothing of clinic A's can appear to clinic B. This is a correctness gate, not a
      security one — the two closed this sprint were security.
      **Why it still belongs before tenant #2:** it gets harder, not easier, to fix later.
      Re-prefixing identifiers after records exist changes IDs already printed on invoices
      and referred to in support. Cheap now, a data migration touching patient-facing
      identifiers later.
      Substantially the same work as the A2 ID-prefix bullet above — do them together.

#### A3. Infrastructure ✅🟡 — remaining
- [ ] ⚠️ **Off-site backup storage — DEFERRED to a free destination (Google Drive via
      `rclone`), no longer Contabo Object Storage and no longer blocked on client
      credentials.** Pre-A6 gate — tracked in full under A5
- [ ] Central vs tenant MySQL user/permission model
- [ ] Script the deploy (currently documented, not automated)
- [ ] Repoint scheduler + vhost when the real app root replaces the smoke deploy

#### A4. Provisioning `[NEW]` ⬜
- [ ] `php artisan tenant:create` — register, create + migrate DB, seed config, first
      admin user, assign subdomain, send setup link
      - **The first admin must come from `php artisan clinic:invite` (built in Sprint 2).**
        Self-registration is gone, and invitations are sent BY an admin — so a freshly
        provisioned clinic has nobody who can invite anyone. `tenant:create` should call
        `clinic:invite <subdomain> <owner email> --role=admin` as its last step; until it
        does, that command is run by hand and is the only way into a new clinic
- [x] ✅ **Reserved-subdomain blocklist — GATE CLOSED (Sprint 2, 2026-08-27).** It really was
      read nowhere: `Clinic::create(['subdomain' => 'api'])` provisioned a clinic and its
      database cleanly, demonstrated before the fix. Now enforced on the **`Clinic` model**
      (`saving`, so a rename onto a reserved label is caught too) — not in a provisioning
      command, because seeders, tinker, the test suite and every future admin screen all reach
      `Clinic::create()` without passing through one. Plus `UsableClinicSubdomain` on the
      sign-up form, which validates the address the clinic NAME would produce.
      Reserved = the configured list **+** every label in front of a central domain, derived
      rather than duplicated. 10 tests, with a revert-to-red run: removing the guard let `www`
      be provisioned and let an existing clinic be renamed onto `api`.
      Deliberately NOT checked at sign-up: availability. Saying "that address is taken" on a
      public form confirms a clinic exists at that name — the enumeration find-your-clinic
      exists to avoid. Uniqueness stays with the unique index at provisioning
- [ ] ⚠️ **Do NOT run `TenantDatabaseSeeder` for a real clinic** — it seeds demo patients.
      A real clinic is provisioned empty apart from its own configuration
- [ ] Idempotent, with rollback on partial failure
- [ ] Deprovision / suspend counterpart
- [ ] Operator runbook

#### A5. Backups ✅🟡 — remaining
- [x] ~~Per-clinic backups once A1 lands~~ **Done** — `tenants:backup` iterates clinics
      and writes one archive each, verified by extracting them and counting rows
      (`hope` archive: 2 Hope rows / 0 Grace; `grace`: the reverse)
- [x] ~~Monitor for scheduler silence~~ **Done (Sprint 2, 2026-08-29).**
      `tenants:backup-monitor` asserts a recent archive EXISTS per clinic (the disk,
      not the run log), is scheduled at 03:00, and is itself listed in
      `schedule:audit`'s expectations so its own silence alarms through `/up`.
      `tenants:backup-clean` likewise prunes per clinic — both previously acted on the
      empty central disk and succeeded nightly while doing nothing

- [ ] 🔴 **PRE-A6 GATE — execute the restore drill for real, per tenant.**
      Not "read the runbook and agree it looks right": actually run it, end to end, on a
      real **AES-encrypted** archive, and confirm the restored database holds that
      clinic's data and no other's.
      **Why this is a gate and not a chore:** the documented procedure was wrong in a way
      that would have failed exactly when it was needed. It said `unzip`, and Info-ZIP
      **cannot read WinZip AES archives at all** — the same trap as Sprint 0, where this
      project's first encryption check "passed" only because `unzip` was not installed.
      The doc is corrected (`docs/BACKUPS_AND_OPS.md` now says `7z`), but a corrected
      doc is a claim, not evidence. **A6 must run a restore, not trust the doc.**

- [ ] 🔴 **PRE-A6 GATE — off-site backup destination. DEFERRED, NOT DROPPED.**
      No longer Contabo Object Storage / blocked on client credentials: the plan is a
      **free destination — Google Drive via `rclone`**. Nothing is wired today.
      **A lost VPS is currently a lost backup.** That is acceptable only while there is
      no real data, which is true now and stops being true at tenant #1.
      **Must close before A6 stands up the first real clinic.**
      Groundwork already in place: `league/flysystem-aws-s3-v3` installed, and `s3`
      listed in `config/tenancy.php` under `filesystem.disks` so any S3-compatible
      destination is per-clinic prefixed rather than every clinic sharing one path

#### A6. Tenant #1 — fresh stand-up 🟡 — infrastructure PROVEN 2026-08-29

- [x] ~~Deploy current main to the real VPS~~ **Done 2026-08-29.** `/var/www/africhart-emr`,
      main `9e2e4b8`. Central DB `africhart_central`; app DB user `africhart` holds
      least privilege — `CREATE, DROP ON *.*` plus full rights only on
      `africhart_central` and the `africhart\_tenant\_%` pattern
- [x] ~~Two throwaway clinics via the real `tenant:create`~~ **alpha / bravo**, provisioned
      as www-data, each with 0 patients / 0 staff and the 10-drug starter catalogue
- [x] ~~Prove isolation on the real box~~ On the public `:443`: cross-tenant sessions
      rejected (302 to the other clinic's own login) while each session works at its own
      clinic (200); invites 200 at their clinic and 404 at the other, both directions;
      independent `ALPH-…-0001` / `BRAV-…-0001` identifiers; per-tenant AES-256 backups
      each containing only their own database; the monitor catching a removed archive;
      silence firing `/up` 500 → 200
- [x] ~~Tear down the smoke deploy~~ `africhart_smoke` database + MySQL user dropped and
      `/var/www/africhart-smoke` removed, after a verified 63KB safety dump
      (`/root/africhart_smoke_pre-cutover.sql`, copy at `/var/www/`)

- [ ] ⚠️ **Provision AfriChart's own clinic as the real Tenant #1.** NOT done — alpha and
      bravo are throwaway test clinics on a development-phase box with no real patient
      data. **The two A5 gates below must close before this**, because this is the step
      that crosses the real-data threshold

#### Post-A6 — found during the A6 cutover

- [ ] 🔶 **PHP/MySQL timezone skew.** PHP runs UTC while the MySQL session is `+02:00`:
      `now()` returned `21:47` against `SELECT NOW()` of `23:47` on the same box.
      Nothing observed broke — every timestamp in play is written by PHP — but a column
      taking a SQL-side default (`CURRENT_TIMESTAMP`) would disagree with an
      application-written one by two hours in the same row.
      **This matters for audit-trail integrity**, which is exactly the thing that has to
      be trustworthy when it is questioned. Close before real patient data.

- [ ] Remove the four obsolete `REGISTER_CODE_*` keys from any other environment they
      survive in — they were stripped from the VPS `.env` at cutover

#### A6 (continued) — original checklist ⬜

> ⚠️ **Two A5 items gate this section — see A5 above.** Both are cheap to skip and
> expensive to have skipped, because each one's failure mode is silence:
> 1. **Run the restore drill for real** on an AES-encrypted archive. The documented
>    procedure was `unzip`-based and could not have worked.
> 2. **Wire the off-site destination** (Google Drive via `rclone`). Until then a lost
>    VPS is a lost backup — fine with no real data, not fine with tenant #1's.

- [ ] Provision AfriChart's own clinic as Tenant #1 using `tenant:create`
- [ ] Provision a second throwaway clinic and **prove isolation between them**
- [ ] Tear down the smoke deploy (`/var/www/africhart-smoke`, its DB and user)
- [ ] Data-protection gate — now due **before first real patient**, not before Phase A

### PHASE B

#### B1. Subscription & billing `[NEW]` ⬜ — design LOCKED (D9–D17), nothing built

Build against ARCHITECTURE §1.1. The decisions are settled; do not re-open them mid-build.

- [ ] Lifecycle: `trialing → active → past_due → suspended (read-only) → cancelled`
- [ ] Charge each cycle from **our own scheduler** — not Paystack Plans (**D9**)
- [ ] Setup fee as a **separate one-off transaction, charged first** (**D10**)
- [ ] Trial with **no card upfront**; collect at day-30 conversion (**D11**)
- [ ] Central-domain webhook: **signature verification + idempotency + event log**,
      all three (**D12**)
- [ ] Group billed as **one subscription × `site_count`** (**D13**)
- [ ] Read-only driven by central `clinics.status`, short-TTL cache in tenant
      middleware; **status never copied into tenant DBs** (**D14**)
- [ ] Dunning: reminders → grace → read-only lockout
- [ ] Never delete clinic data for non-payment
- [ ] ⚠️ **Gate before launch (D17):** the whole lifecycle — subscribe → charge → fail →
      dunning → read-only → recover — proven on Paystack **test keys**. The first real
      dunning must never be against a real clinic
- [ ] ⚠️ **Standing constraint:** patient→clinic and clinic→AfriChart payments never share
      an account, a database or a code path. The highest-risk part of B1

#### B2. Plans, gating & metering `[NEW]` ⬜
- [ ] `plan_features` map in the central DB
- [ ] Gate enforced in UI **and** server-side (architecture doc: "hiding alone is theater")
- [ ] Keep audit logging always-on; gate **visibility/export**, never the trait
- [ ] Usage counters (seats, sites)
- [x] ~~⚠️ Confirm pricing~~ **LOCKED 2026-08-25** (§0.1): Starter ₦45k / Clinic ₦85k /
      Group ₦65k-per-site, with setup fees ₦75k / ₦120k / ₦150k. Now in the `plans`
      table as the single source of truth, which is also what B2 gates against
- [ ] Per-site metering for Group — the price basis is modelled; enforcing seat/site
      counts against it is still B2's job

#### B3. Public marketing site `[NEW]` ✅ — COMPLETE 2026-08-22
Root domain is reassigned at project end (settled). Built behind that.
- [x] Home · Features · Pricing · About · **Contact** · Demo · Get started · Login entry
- [x] Legal: Privacy, Terms, Data-Processing (each carries a "pending legal review" notice)
- [x] Design language from the platform spec (General Sans self-hosted; warm off-white +
      near-black; 8px radii; no shadows) plus a motion scale and reduced-motion guard
- [x] Tier-2 auth pages restyled onto a shared shell; login lockout feedback fixed
- [x] Navigation: Features · Company (dropdown) · Pricing · Contact, with a fixed full-screen
      mobile menu (blurred backdrop, CTAs footed, body scroll-locked, no page-push)
- [ ] ⚠️ **Confirm the published prices.** SOW Appendix 1 is blank and `/pricing` is live with
      the platform-spec proposal (₦25k / ₦50k / ₦40k-per-site)
- [ ] Deferred to A1/A2: find-your-clinic (needs the registry) · `/invite/{token}` (needs
      per-clinic single-use tokens)

#### B4. In-clinic account surfaces `[NEW]` ✅🟡 — buildable scope COMPLETE 2026-09-04

Commits `fd6e729` → `8f1fff4`. Tenancy suite **70/70, 358 assertions** (was 65).

**Built**

- [x] ✅ **Settings hub** — `<x-settings-shell>` + `<x-form-field>`, the latter extracted
      VERBATIM from the app's existing input markup rather than invented, so the settings
      screens are the same visual language and not a parallel one
- [x] ✅ **Clinic Profile** — name and `id_prefix` write to CENTRAL `clinics`; address,
      phone, email, fee and timezone to tenant `settings`. That split is decision 1, not
      plumbing: a name is identity, and a second editable copy would drift from the
      registry's. The prefix is editable only while the clinic has zero records — after
      that it is read-only with the reason on screen, because it is already stamped into
      every identifier issued
- [x] ✅ **Clinic identity** — the on-screen invoice had NO clinic name while carrying a
      Print button, so "Print" handed a patient an anonymous document while "Download PDF"
      handed them a headed one. Fixed first as the sharpest instance. Page titles composed
      in the layout (not 18 views), clinic name in the topbar (not 4 dashboards).
      Vendor chrome deliberately untouched: sidebar, pre-auth layouts, API title
- [x] ✅ **Timezone, stored and consumed** — the connection is pinned to UTC (`fd6e729`);
      `todayRange()` makes "today" the CLINIC's day. Not cosmetic: `getNextQueueNumber()`
      counts today's rows, so a Lagos clinic crossing UTC midnight could reissue a live
      queue number
- [x] ✅ **Team & Seats** — `/staff` folded in, not rebuilt. The invite link is now flashed
      once on creation: it is stored only as a hash, so a failed or stubbed email used to
      destroy the invitation outright. Seats are counted and displayed but **not enforced** —
      a seat limit is a plan entitlement, and that is B2 behind B1
- [x] ✅ **Drug Catalogue** — folded in; per-tenant since before B4 and left that way
- [x] ✅ **Branding** — logo per-tenant in `storage/tenant<uuid>/`, served by OUR route.
      NOT stancl's asset route, which is bound to `InitializeTenancyByDomain` and calls
      `$tenant->domains()` — a relation `Clinic` does not have. That is the A6 defect
      verbatim and was nearly reintroduced
- [x] ✅ **First-run setup wizard** — fills only what `tenant:create` leaves undone,
      measured against a real minimal provision. `setup_completed_at` is a key/value row,
      so **no migration reaches existing tenants**

**Deferred to B1 — cannot be built before billing exists**

- [ ] ⬜ **Billing & Plan** settings section — listed in the hub but **shown disabled with a
      reason**, deliberately rather than hidden: the dependency is then visible to an owner,
      not merely recorded here
- [ ] ⬜ Plan/usage visibility + upgrade prompts — needs D16's metering
- [ ] ⬜ Billing-state screens (trial ending, payment failed, read-only lockout) — needs
      D14's `clinics.status` webhook path

**⚠️ Security fix inside this range — must lead the deploy notes.** Step 2 shipped the
settings block sitting BETWEEN two `role:admin` groups and inside neither, under a comment
reading "admin only". It looked gated, was documented as gated, and was not: **a nurse
could rename the clinic, change the consultation fee, or edit the ID prefix.** Closed in
`7a13f11`. The hole is live in production until this deploys. Found only by attempting the
page AS the wrong role — every settings screen now carries a wrong-role-is-blocked check,
because the gated and ungated blocks look alike in the routes file.

#### B5. Super-admin panel `[NEW]` ⬜
- [ ] ⚠️ **Get Started promises an email nobody sends — and nobody is told.**
      `POST /signup` writes one `marketing_leads` row and flashes a toast. That is the
      whole action: `MarketingLead` appears in exactly two places in `app/` — the import
      and the `create()` — with no observer, listener or notification. Meanwhile the page
      says *"Where we send your login details"*, *"we will set your clinic up and send
      login details shortly"*, and *"we create your clinic … and email you the link"*.
      **No route or view surfaces leads either**, so a submission is visible only by
      querying the database — the promise depends on an operator noticing a row that
      nothing tells them about. Two fixes, both belong here:
      (a) an **operator notification** on lead creation, and
      (b) **honest copy** — "we'll be in touch" rather than "email you the link" —
      until self-serve provisioning actually exists.
      Investigated 2026-08-29; deliberately not fixed in that pass.
- [ ] Clinic list + subscription status · provision/suspend
- [ ] Impersonation — **audited and tightly access-controlled**
- [ ] Platform metrics (needs the aggregation layer — see §6)

#### B6. Product telemetry `[NEW]` ⬜
- [ ] Self-hosted; activation, feature usage, lifecycle transitions
- [ ] Needed for the usage-gated trial ("active receptionist in last 7 days")

#### B7. Compliance ⬜
- [ ] Per-clinic DPA template
- [ ] Documented data-isolation guarantee (NDPA/NDPR)
- [ ] Breach-response plan
- [ ] Legal certification is the Client's responsibility (SOW §9.3)

---

## 5.1 Housekeeping — small, known, not yet done

- [ ] ⚠️ **The default test command tests nothing.** `phpunit.xml` registers only
      `tests/Unit` and `tests/Feature`, which between them hold Laravel's two stock
      example tests. Every real test in this project lives in `tests/Tenancy/` and runs
      only under `phpunit.tenancy.xml` (via `composer test:tenancy`), because it needs
      real MySQL. So `php artisan test` reports green having exercised nothing — a trap
      for anyone, including CI, who reasonably assumes otherwise. Either point the
      default at the real suite or make it fail loudly with a pointer to `test:tenancy`
- [ ] The tenancy suite **cannot be run twice concurrently**: both processes share
      `africhart_testing` and the `africhart_testtenant_` prefix, and teardown drops
      databases BY PREFIX — so a second run deletes the first run's tenants mid-test.
      It surfaces as unrelated-looking random failures. A lock file, or a per-process
      prefix, would remove the foot-gun
- [ ] `docs/WALKTHROUGH.md` still references the deleted `database/schema/*.sql` dumps
      (~line 1318) and the removed `REGISTER_CODE_*` config (~line 1065). Left alone in
      Sprint 2 only because the file had unrelated uncommitted edits at the time

## 6. Paystack — architecture LOCKED (2026-08-29)

The nine questions that stood here are **resolved**. They are recorded as decisions **D9–D17** in
[`ARCHITECTURE.md`](ARCHITECTURE.md) §1.1, with the reasoning, and are not to be re-opened during
the B1 build. This section is the index; the architecture doc is the record.

### The two integrations must never touch — STANDING CONSTRAINT

| | **Patient billing** | **Clinic subscription billing** |
|---|---|---|
| Who pays whom | Patient → Clinic | Clinic → AfriChart |
| Lives in | **Tenant DB** | **Central DB** |
| Status | Built (cash/manual); online payment is platform-spec A2-7 | ⬜ SOW B1 |
| Merchant account | **The clinic's own** | **AfriChart's** |
| Commercials | Phase-1 carry-over, **no extra fee** (SOW §6) | Part of Phase 2 |

> They share a gateway vendor and nothing else — not an account, not a database, not a code path.
> Patient payments must settle into **the clinic's** Paystack account; if they ever land in
> AfriChart's, AfriChart is holding clinical revenue, which is a regulatory and accounting problem
> discovered late and by an auditor. **The highest-risk part of B1.** A constraint, not a decision:
> there is no trade-off and no acceptable alternative arrangement.

### The nine, resolved

| # | Question | Decision |
|---|---|---|
| **D9** | Native Plans or our own scheduler? | **Our own scheduler.** Group is per-site and changes mid-cycle; native Plans cannot express variable pricing or proration cleanly. Paystack moves the money; we keep the cycle |
| **D10** | Setup fee: separate transaction or invoice line? | **Separate one-off transaction, charged first**, before the subscription begins. Keeps the day-30 refund clean |
| **D11** | Trial: hold a card, or collect at conversion? | **Collect at conversion**, no card upfront. Fits the operator-driven, high-touch sale |
| **D12** | Webhook shape | **Central-domain endpoint**; signature verification, idempotency and an event log **all mandatory** |
| **D13** | Group tier: quantity or N subscriptions? | **One subscription with a `site_count` quantity.** One group, one billing relationship, one dunning state |
| **D14** | How does tenant middleware learn payment status? | Central **`clinics.status`**, read per request behind a **short cache TTL**. Never pushed into tenant DBs |
| **D15** | Refund path for the day-30 promise | **Manual, operator-initiated** via Paystack's refund API against the standalone setup-fee transaction, logged. B5 panel |
| **D16** | Cross-tenant aggregation for MRR | **Deferred to B5/B6 — does not block B1.** MRR is a central query; *usage* aggregation is a B6 telemetry problem (lean: push-summaries) |
| **D17** | Test mode | **Full dunning lifecycle on test keys before launch.** The first real dunning must never be against a real clinic |

### ⚠️ Pricing in this section was stale

Earlier drafts of §6 quoted **₦40k per site** and a **₦50k–₦100k** setup fee. Both are wrong.
The locked figures are **§0.1 / D6**:

| Tier | Monthly | Setup |
|---|---|---|
| Starter | ₦45,000 | ₦75,000 |
| Clinic | ₦85,000 | ₦120,000 |
| Group | ₦65,000 **per site** | ₦150,000 |

Build against the `plans` table, which is the single source of truth. Annual pricing remains
unconfirmed — do not derive one.

---

## 7. Architecture doc — what is now out of date

The attached roadmap is directionally still right: one codebase, many databases,
subdomain routing, central registry, `stancl/tenancy`, Paystack. **The core decisions
hold.** What has drifted:

| Section | Says | Reality |
|---|---|---|
| Stage 0 | "Single client, single-tenant, on existing hosting. **Live and working.**" | **Not launched.** No clinics signed, no real patient data. The cPanel site is a demo |
| Stage 1 | "Stand up the Contabo VPS (nginx wildcard, wildcard SSL, PHP-FPM, MySQL, Redis, Supervisor, cron, object storage)" | **~90% done.** Only object storage outstanding |
| Stage 1 | "Migrate the existing client to tenant #1" | **No longer applicable** — nothing to migrate |
| Stage 1 | "In parallel: Doc 2 Critical Before Launch fixes" | **Done** (`247ce44`) |
| Stage 3 | "Monitoring/alerting" | **Already built** at server level — out of sequence, done early |
| Stage 3 | "rate-limited auth" | **Done** (`throttle:5,1`) |
| Stage 3 | "encryption at rest for backups" | **Done 2026-08-21** (AES-256, restore rehearsed) |
| §4 stack | Redis for "cache, sessions, queue driver" | **Diverged** — currently `database` for all three. See below |
| §4 stack | "Object storage (Contabo S3-compatible)" | Not configured — needs Client credentials |
| §7 debt | "deployment becomes a real pipeline (git → composer → build → tenant migrate)" | Proven manually on 2026-08-21; not yet scripted |

### ✅ The Redis-vs-database decision — SETTLED 2026-08-23

The architecture doc specifies Redis for cache, sessions and queue. The current deploy
uses the `database` driver for all three. That is **not simply a mistake to correct** —
it changes the tenancy design:

- **`database` driver** → cache, sessions and jobs live *inside each tenant's database*,
  so they are isolated **automatically and by construction**. Slower, but the isolation
  story is trivially defensible to a wary clinic owner — which is exactly the argument
  §1 of the architecture doc uses to justify DB-per-tenant in the first place.
- **Redis** → one shared store. Isolation depends on correct per-tenant prefixing via
  the package's bootstrappers. Faster, and Stage 4 horizontal scaling assumes
  "sessions already in Redis". A prefixing bug is a cross-tenant data leak.

**Decided: `database` for cache, sessions and queue.** Recorded as decision **D1** in
[`ARCHITECTURE.md`](ARCHITECTURE.md) §1, which is now the authoritative statement. Redis is
**deferred to Stage 4**, not rejected — and when it is revisited, the tenancy bootstrappers'
key-prefixing must be audited as a *security* change, not a performance one.

- [x] ~~Rewrite the architecture doc against current reality and commit it to `plans/`~~
      **Done 2026-08-23** → [`ARCHITECTURE.md`](ARCHITECTURE.md). §10 of that doc records what
      drifted; the Redis-vs-database question below is settled there as decision **D1**

---

## 8. Where the deploy stands right now

The smoke deploy at `https://africhartemr.com` now carries all Sprint 0 changes:
scheduler live, encrypted backups running, wildcard routing correct, PHP-FPM sized,
proxy spoofing closed.

**Two housekeeping notes:**

1. ~~Branch `fix/sprint-0-hardening` is committed locally but not pushed.~~
   **Resolved 2026-08-22** — `9a52dd1` is an ancestor of `feature/marketing-site` (`5a7000a`),
   which is on GitHub. Sprint 0 is safe.
   **Still outstanding:** the four changed PHP files were copied to the server directly to verify
   them, so the server checkout remains unreconciled with git. Pull on the server before the next
   deploy.
2. `git config core.fileMode false` was set on the server checkout. The permissions pass
   flipped modes on `artisan` and eleven `.gitignore` files, which would otherwise bury
   every future `git pull` in mode noise. (Minor repo hygiene: those `.gitignore` files
   are committed as `100755` and `artisan` as `100644` — backwards.)

---

## 9. Suggested sequence from here

**~~Sprint 1 — A1 tenancy~~ ✅ COMPLETE 2026-08-25** (the long pole, now behind us)
Architecture doc → central registry → users→staff rename → subdomain identification →
per-request switching → migration split → scheduler → **isolation suite** (28 tests,
143 assertions, sabotage-tested)

**~~Sprint 2 — A2 + A3 remainder + A4~~ ✅ COMPLETE 2026-08-29**
Invite codes (the hard gate) → ID prefix, fee, catalogue scoping → `tenant:create` with
the reserved blocklist and rollback. Off-site backups moved out of this sprint by
decision rather than being finished in it — see the gates below.

**~~Sprint 3 — A6 → Phase A infrastructure acceptance~~ ✅ COMPLETE 2026-08-29**
main deployed to the real VPS → two throwaway clinics via `tenant:create` → isolation
proven on the public `:443` (cross-tenant sessions rejected, cross-clinic invites 404,
independent identifiers, per-tenant encrypted backups, monitor catching a removed
archive, silence firing `/up`) → smoke deploy torn down behind a verified dump.

---

### What is actually next

**1. The pre-real-clinic gates — A5.** ⚠️ **CLIENT-BLOCKED** on the backup account.
Nothing else should cross the real-data threshold until both are closed:
  - **Off-site backup destination** — Google Drive via `rclone`, a free destination. A
    lost VPS is currently a lost backup. Acceptable now (throwaway clinics, no real
    patient data); not acceptable for a real clinic.
  - **Execute the restore drill for real** on an AES-encrypted archive. The documented
    procedure was `unzip`-based and could not have worked; the doc is corrected but a
    corrected doc is a claim, not evidence.

**2. The PHP/MySQL timezone skew.** Surfaced during the A6 cutover — PHP runs UTC while
the MySQL session runs `+02:00`. Nothing observed breaks today, because every timestamp
in play is written by PHP, but a SQL-side default would disagree with an
application-written one by two hours in the same row. **This is an audit-trail integrity
issue and must close before real patient data.** Tracked under *Post-A6* below.

**3. Phase B.** In this order:
  - **B4 — Settings hub** first; the groundwork exists (tenant `settings` table, the
    per-clinic values A2 shipped), so this is the cheapest real progress.
  - **B1 + B2 — billing.** Design is now locked as well as the gateway: build against
    **D9–D17** (ARCHITECTURE §1.1). Two things govern the build — the merchant-account
    split (patient→clinic and clinic→AfriChart never touch) and **D17**, the full dunning
    lifecycle proven on test keys before launch.
  - **B5 + B6 + B7** — super-admin → telemetry → DPA + isolation guarantee.

**Timeline:** Phase A infrastructure is complete. What remains before a real clinic is
not development time but two backup gates, one of which is blocked on the Client. The
isolation suite should still not be compressed — it is what the entire commercial
proposition rests on, and it is now the evidence behind a claim made on production.

---

*Updated 2026-08-29 after the A6 cutover. Repo at `d5d89e9` (branch `main`).*

*A4, the Part 4 backup work and A6 are covered by [`PHASE2_PROGRESS_2026-08-29.md`](PHASE2_PROGRESS_2026-08-29.md) — report #4, written against `97aadf9`, which closed the gap where that record was commit history alone.*
