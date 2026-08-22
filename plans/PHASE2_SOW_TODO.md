# Phase 2 SaaS — Scope-of-Work To-Do

**Source:** SCOPE OF WORK — AfriChart EMR SaaS Platform (Phase 2), Ref `ACT-DEV-006`
**Architecture:** *AfriChart EMR — SaaS Scaling Architecture & Roadmap* (see [§7 reconciliation](#7-architecture-doc--what-is-now-out-of-date) — parts of it are now out of date)
**Updated:** 2026-08-22 · **Sprint 0 complete · B3 complete**
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
| Paystack architecture | **To be designed** — see [§6](#6-paystack--architecture-to-be-designed) |

> **The A6 change matters more than it looks.** With no live clinic and no patient
> data, the data-protection gate is no longer blocking Phase A. It moves to
> *before first real patient*, not *before migration*. That removes the single
> heaviest dependency from the Phase A critical path.

---

## 1. Scope map at a glance

| SOW item | | Status | Where it stands |
|---|---|---|---|
| **A1** | Tenancy architecture `[NEW]` | ⬜ | Zero tenancy packages in the repo |
| **A2** | Per-tenant configuration | 🟡 | Drug catalogue DB-backed; prefix/fee/invites still global |
| **A3** | VPS infrastructure `[NEW]` | 🟡 **~90%** | Only off-site object storage outstanding |
| **A4** | Provisioning command `[NEW]` | ⬜ | Depends on A1 |
| **A5** | Backups | 🟡 | **Now live, encrypted, restore rehearsed.** Off-site + per-clinic outstanding |
| **A6** | Tenant #1 | ⬜ | Now a fresh stand-up, not a migration |
| **B1** | Subscription & billing `[NEW]` | ⬜ | Architecture to be designed (§6) |
| **B2** | Plans, gating & metering `[NEW]` | ⬜ | Tiers designed, nothing built |
| **B3** | Public marketing site `[NEW]` | ✅ | **Complete 2026-08-22** — 10 pages + 3 legal docs. See `PHASE2_PROGRESS_2026-08-22.md` |
| **B4** | In-clinic account surfaces `[NEW]` | 🟡 | Two seams shipped; wizard + Settings hub absent |
| **B5** | Super-admin panel `[NEW]` | ⬜ | Depends on A1 |
| **B6** | Product telemetry `[NEW]` | ⬜ | Not started |
| **B7** | Compliance | 🟡 | DPA template + Privacy + Terms written 2026-08-22 (pending legal review); isolation guarantee + breach plan not |

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

- **Off-site backup storage** — needs Contabo Object Storage credentials, which only
  the Client holds. Backups are currently **local to the box**, which means a lost VPS
  is a lost backup. This is the single most important remaining Sprint 0 item.

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

- [ ] Add "session does not cross subdomains" to the A1 isolation tests
- [ ] Decide unknown-subdomain UX: `444` is right for junk, but an unprovisioned
      *clinic* subdomain should probably get a friendly "no such clinic" page

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

- [ ] Move scheduled work to per-tenant execution (`tenants()->each(...)` or the
      package's tenant-aware scheduling)
- [ ] Per-tenant backups, each verifiable independently
- [ ] Cross-tenant scheduled jobs (trial expiry, dunning) run **centrally**, not per tenant
- [ ] Add `withoutOverlapping()` — a slow backup across N tenant DBs will outlive its minute
- [ ] Alert on scheduler *silence*, not just failure. A cron that stops firing produces
      no error; the weekly health digest should assert "backup ran in the last 24h"
- [ ] Repoint the cron path when the real app root replaces `/var/www/africhart-smoke`

---

## 5. To-do — remaining work

### PHASE A

#### A1. Tenancy architecture `[NEW]` ⬜
- [ ] Adopt `stancl/tenancy` (the architecture doc's recommendation — still sound)
- [ ] **Rewrite the architecture doc** — see §7; several sections are stale
- [ ] Central DB: clinic registry (name, subdomain, tenant DB, status) + platform admins + plans
- [ ] Subdomain tenant identification + per-request connection switching
- [ ] Split migrations into central vs tenant sets
- [ ] Per-tenant DB creation + migration commands
- [ ] ⚠️ **Decide cache/session/queue drivers under tenancy** — see §7
- [ ] **Isolation test suite** — must prove: clinic A cannot read clinic B's data;
      sessions do not cross subdomains; cache keys do not collide; queued jobs run
      against the right tenant

#### A2. Per-tenant configuration ⬜🟡
- [ ] ID prefix `ACH-` — hardcoded in `PatientService.php:115`,
      `ConsultationService.php:87`, `InvoiceService.php:167`
- [ ] Consultation fee — `config/billing.php` → tenant settings
- [ ] Drug catalogue — scope the existing `medications` table per tenant 🟡
- [ ] Invite codes — `.env` `REGISTER_CODE_*` (`RegisterRequest.php:32`) → in-app,
      per-clinic, single-use, expiring
- [ ] Brand-string sweep (architecture doc §7: must be complete **before tenant #2**)

#### A3. Infrastructure ✅🟡 — remaining
- [ ] ⚠️ **Off-site backup storage** (Contabo Object Storage) — needs Client credentials
- [ ] Central vs tenant MySQL user/permission model
- [ ] Script the deploy (currently documented, not automated)
- [ ] Repoint scheduler + vhost when the real app root replaces the smoke deploy

#### A4. Provisioning `[NEW]` ⬜
- [ ] `php artisan tenant:create` — register, create + migrate DB, seed config, first
      admin user, assign subdomain, send setup link
- [ ] Idempotent, with rollback on partial failure
- [ ] Deprovision / suspend counterpart
- [ ] Operator runbook

#### A5. Backups ✅🟡 — remaining
- [ ] Per-clinic backups once A1 lands
- [ ] Off-site destination (blocked on credentials)
- [ ] Re-rehearse restore **per tenant** after A1
- [ ] Monitor for scheduler silence

#### A6. Tenant #1 — fresh stand-up ⬜
- [ ] Provision AfriChart's own clinic as Tenant #1 using `tenant:create`
- [ ] Provision a second throwaway clinic and **prove isolation between them**
- [ ] Tear down the smoke deploy (`/var/www/africhart-smoke`, its DB and user)
- [ ] Data-protection gate — now due **before first real patient**, not before Phase A

### PHASE B

#### B1. Subscription & billing `[NEW]` ⬜ — architecture in §6
- [ ] Lifecycle: `trialing → active → past_due → suspended (read-only) → cancelled`
- [ ] Paystack recurring naira billing
- [ ] Webhook handler with **signature verification**
- [ ] Dunning: reminders → grace → read-only lockout
- [ ] Never delete clinic data for non-payment

#### B2. Plans, gating & metering `[NEW]` ⬜
- [ ] `plan_features` map in the central DB
- [ ] Gate enforced in UI **and** server-side (architecture doc: "hiding alone is theater")
- [ ] Keep audit logging always-on; gate **visibility/export**, never the trait
- [ ] Usage counters (seats, sites)
- [ ] ⚠️ Confirm pricing: SOW Appendix 1 is blank; the platform spec proposes
      Starter ₦25k / Clinic ₦50k / Group ₦40k-per-site

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

#### B4. In-clinic account surfaces `[NEW]` ⬜🟡
- [ ] First-run setup wizard (profile → fee → catalogue → invite staff → first patient)
- [ ] Settings hub: Clinic Profile · Billing & Plan · Team & Seats · Branding · Drug Catalogue
      (🟡 `/drug-catalog` and `/staff` already exist — fold them in)
- [ ] Plan/usage visibility + upgrade prompts
- [ ] Billing-state screens (trial ending, payment failed, read-only lockout)

#### B5. Super-admin panel `[NEW]` ⬜
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

## 6. Paystack — architecture to be designed

To be worked through together. Capturing the shape and the open questions now.

### The two integrations must never touch

| | **Patient billing** | **Clinic subscription billing** |
|---|---|---|
| Who pays whom | Patient → Clinic | Clinic → AfriChart |
| Lives in | **Tenant DB** | **Central DB** |
| Status | Built (cash/manual); online payment is platform-spec A2-7 | ⬜ SOW B1 |
| Merchant account | **The clinic's own** | **AfriChart's** |
| Commercials | Phase-1 carry-over, **no extra fee** (SOW §6) | Part of Phase 2 |

> The merchant-account split is the part most likely to go wrong. Patient payments must
> settle into **the clinic's** Paystack account, not AfriChart's — otherwise AfriChart
> is holding clinical revenue, which is a regulatory and accounting problem nobody wants.

### Questions to resolve before building

- [ ] **Subscription mechanism** — Paystack Plans + Subscriptions, or charge-on-schedule
      from our own scheduler? Plans are simpler; scheduler gives control over proration,
      the ₦40k-per-site Group tier, and mid-cycle seat changes.
- [ ] **Setup fee** (₦50k–₦100k one-time) — separate one-off transaction before the
      subscription starts, or first-invoice line item?
- [ ] **Trial mechanics** — setup-fee-first then 30 days free, gated on *daily usage*.
      Does Paystack hold a card through the trial, or do we collect at conversion?
- [ ] **Webhook endpoint** — lives on the root/central domain. Needs signature
      verification, idempotency (Paystack retries), and an event log.
- [ ] **Group tier** — ₦40k **per site**. Quantity-based subscription, or N subscriptions?
      Affects how seats/sites metering is modelled.
- [ ] **Failed payment → read-only** — how does tenant middleware learn status? Cached
      from central per request, or pushed on webhook? Cache TTL vs how fast a lockout lifts
      after payment.
- [ ] **Refunds** — the "full setup-fee refund if unused by day 30" promise needs a path.
- [ ] **Cross-tenant aggregation for MRR** — architecture doc §2 flags that patient data
      cannot be JOINed across tenant DBs. Subscription data is central so **MRR is easy**;
      it is *usage* metrics that need the aggregation layer. Decide push-summaries vs
      scheduled roll-up when B5/B6 are built.
- [ ] **Test mode** — Paystack test keys through the whole dunning lifecycle before launch.

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

### ⚠️ The Redis-vs-database decision the doc assumes away

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

Recommendation: **stay on `database` through Stage 1–2**, where correctness and a clean
isolation story matter more than throughput, and revisit Redis when load justifies it.
Either way this needs to be a recorded decision, because Stage 4 is written assuming Redis.

- [ ] Rewrite the architecture doc against current reality and commit it to `plans/`
      (A1 depends on it; the platform spec references it throughout and it isn't in the repo)

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

**Sprint 1 — A1 tenancy** (the long pole)
Rewrite the architecture doc → central DB + registry → subdomain identification →
per-request switching → migration split → **isolation tests** (data, session, cache, queue)

**Sprint 2 — A2 + A3 remainder + A4**
Per-tenant config → off-site backups → provisioning command

**Sprint 3 — A6 → Phase A acceptance**
Tenant #1 fresh → second clinic → prove isolation → per-tenant backups + restore →
tear down the smoke deploy

**Sprint 4 — B4 + the last two Tier-2 pages** (the §6.1 ₦200,000 line item; B3 half already done)
Setup wizard → Settings hub → find-your-clinic + invite acceptance (buildable once A1/A2 land)

**Sprint 5 — B1 + B2**
Paystack architecture agreed (§6) → subscriptions → webhooks → dunning → gating + metering

**Sprint 6 — B5 + B6 + B7 → Phase B acceptance**
Super-admin → telemetry → DPA + isolation guarantee

**Timeline:** SOW estimates Phase A at ~3–4 weeks from kickoff. A3 at ~90% and the
migration dependency removed pull that in materially. A1 remains the long pole, and the
isolation test suite should not be compressed — it is what the entire commercial
proposition rests on.

---

*Updated 2026-08-21 after Sprint 0. Repo at `10721b2` (branch `fix/sprint-0-hardening`).*
