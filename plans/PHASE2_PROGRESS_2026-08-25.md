# Phase 2 — Progress Report #2

**Period:** 2026-08-23 → 2026-08-25 (continues [`PHASE2_PROGRESS_2026-08-22.md`](PHASE2_PROGRESS_2026-08-22.md))
**Repo at:** `11872c0` on `main` · 15 commits · 102 files · +5,020 / −487
**Companions:** [`ARCHITECTURE.md`](ARCHITECTURE.md) · [`PHASE2_SOW_TODO.md`](PHASE2_SOW_TODO.md)

Legend: ✅ done · 🟡 partial · ⬜ not started · ⚠️ decision or input needed

---

## 1. Headline

**A1 — tenancy architecture — is complete and through its acceptance gate.** It was
the long pole for the whole of Phase A, and everything downstream of it (A2, A4, A6,
B4, B5) is now unblocked.

| | |
|---|---|
| SOW items completed | **A1** — was ⬜, is now ✅ |
| SOW items advanced | A5 → per-tenant backups live; B7 → isolation guarantee now demonstrable |
| Acceptance gate | 28 tests, 143 assertions, real MySQL, two real provisioned tenants |
| First real test coverage | The project had 2 stub tests. It now has a suite that found 3 live bugs |
| Still blocked on the Client | Contabo Object Storage credentials · Paystack decisions · Appendix 1 pricing |

**The headline number that is not a number:** the isolation suite found that the
**cache was not isolated at all** — every tenant's cache read and write was going to
the central database. That defect was written by me in step 1, reviewed, committed,
and would have shipped. §5 below explains it, because how it was found matters more
than that it was fixed.

---

## 2. A1, step by step

| # | Step | Commit | What it delivered |
|---|---|---|---|
| 0 | Architecture doc | `bfd42ba` | Five decisions locked with rationale, plus the blast radius of the rename measured against the repo |
| 1 | Adopt stancl | `bf2a798` | v3.10.1, multi-database mode confirmed from the booted app |
| 2 | Central registry | `b7a988e` | `clinics`, `platform_admins`, `plans`; migrations split central/tenant |
| 3 | `users` → `staff` | `43a413f` | 48 files, one atomic commit, Sanctum morph map in the same commit |
| 4 | Subdomain routing | `753bdb3` | Per-request connection switching; friendly "no such clinic" |
| — | Find your clinic | `27c0d01` | T2.2 — the central staff entry point |
| — | Test database | `1358f3a` | Dedicated MySQL, teardown guards proven by trying to break them |
| 5 | Scheduler | `d626a5e` | Per-tenant backups, central-once tasks, silence detection |
| 6 | Isolation suite | `11872c0` | **The acceptance gate** |

Also shipped in the same period, closing out B3: the full-screen mobile menu, the
navbar restructure, and city becoming required on sign-up.

---

## 3. What the acceptance gate proves

Four guarantees. Every test **attempts a cross-tenant leak and asserts it fails** —
a test showing that clinic A can read clinic A's own data proves nothing, because a
single shared database passes it too.

| § | Guarantee | The leak attempted | Result |
|---|---|---|---|
| 6.1 | **Data** | From A, direct primary-key access to B's patient, consultation, invoice, audit and staff rows | Never returns B's record. A sees 3 patients, not 10 |
| 6.2 | **Session** | Real login on A, cookie replayed against B | 302 to B's login. A's session id is absent from B's `sessions` table |
| 6.3 | **Cache** | Same key written in A, read in B | `null`. Row physically in A's table, B's empty |
| 6.4 | **Queue** | Job dispatched in A, processed while the worker sits *inside* B | Writes to A. B untouched |

Plus: `config('session.domain')` is null — the guard that fails loudly the moment
somebody scopes the cookie to the parent domain and shares one session across every
clinic.

### The suite was sabotaged to prove it detects breakage

A green run is not evidence. This is the same lesson as the backups in §5 — three
deliberate breakages, each confirmed to fail the suite:

| Sabotage | Result |
|---|---|
| Remove the cache bootstrapper | 5 tests fail — `LEAK: B read [ALPHA-SECRET]` |
| Set `SESSION_DOMAIN` | Guard fires with its own message |
| Point two clinics at one database | `Two clinics must not share a database`; A sees 10 patients including all 7 of B's |

> The first shared-database sabotage **passed**, which would have suggested the suite
> was worthless. It was the sabotage that was wrong — it repointed the tenant *after*
> it had already written, so nothing was ever shared. Corrected before drawing any
> conclusion from it.

---

## 4. Bugs the work found — all in code that looked finished

### 4.1 🔴 The cache was never isolated

Every tenant's cache read and write went to `africhart_central`. A probe written in
clinic A was readable in clinic B; A's own cache table held zero rows; flushing A
destroyed B's data.

Disabling stancl's `CacheTenancyBootstrapper` in step 1 was correct — it routes every
call through `->tags()`, which Laravel's database store does not support. But the
reasoning recorded beside it was wrong: it claimed `DatabaseTenancyBootstrapper`'s
connection swap would redirect the cache anyway. **It does not.** `CacheManager`
resolves a store once, and `DatabaseStore` holds a live `Connection` **object**, not a
connection name.

D1 claimed cache isolation was structural. That claim was false for four days and
only the acceptance gate exposed it.

### 4.2 🔴 Per-tenant backups archived the central database twice

The command written specifically to prevent §7's failure mode reproduced it. It
reported `✓ hope ✓ grace, 0 failed` and both archives contained
`mysql-africhart_central.sql`.

Two silent causes: `--db-name` *filters* spatie's configured database list rather than
adding to it, and spatie's `BackupCommand` takes its config by **constructor
injection**, resolved before any runtime override, unless `--config` is passed.

Caught only by extracting the archives and counting rows.

> **A green exit code was not evidence. The contents were.** This is now the standing
> verification discipline on this project, and it is what caught 4.1 as well.

### 4.3 Three more, each invisible from reading the code

- **Tenant routes silently overwrote central ones.** Laravel's route collection keys
  by method+domain+uri, so a tenant `GET /` replaced the marketing home page and the
  public site began 404ing. Fixed by scoping tenant routes to the subdomain.
- **`central_domains` had drifted from `root_domain`** — two independent lists, so
  changing the domain left every tenant route unreachable. Now derived.
- **`Job::dispatch()` pushes in its destructor**, so `fn () => Job::dispatch(...)`
  hands the pending job out of a tenant-scoped closure and it is built with no tenant.
  Any application code shaped `return SomeJob::dispatch(...)` inside a tenant callback
  has this bug. Pinned as its own test.

### 4.4 One that was not a bug, investigated rather than assumed

The session test first showed clinic B returning **200, authenticated as A's doctor** —
indistinguishable from a breach. It was not one: **B's sessions table had zero rows**,
so B never read a session at all. Laravel's test client reuses one container across
requests within a test method and the auth state simply never left it.

Worth recording because the honest conclusion required checking the evidence rather
than either panicking or waving it away.

---

## 5. Corrected SOW status

### Phase A

| Item | Was | Now | Where it stands |
|---|---|---|---|
| **A1** Tenancy | ⬜ | **✅** | Complete and through the acceptance gate |
| **A2** Per-tenant config | 🟡 | 🟡 | Unblocked. `ACH-` prefix, fee, invite codes and catalogue scoping still outstanding |
| **A3** Infrastructure | 🟡 ~90% | 🟡 ~90% | Unchanged — off-site object storage still the only gap |
| **A4** Provisioning | ⬜ | ⬜ | Unblocked. `Clinic::create()` already runs the real create+migrate pipeline; `tenant:create` wraps it |
| **A5** Backups | 🟡 | 🟡 | **Per-tenant backups now live and verified by archive contents.** Off-site destination still blocked |
| **A6** Tenant #1 | ⬜ | ⬜ | Unblocked, but see the hard gate in §7 |

### Phase B

| Item | Was | Now | Where it stands |
|---|---|---|---|
| **B1** Billing | ⬜ ⚠️ | ⬜ ⚠️ | Nine questions still open. Trial-audit seam built and deliberately report-only |
| **B2** Plans & gating | ⬜ | ⬜ | `plans` table and feature map now exist to gate against |
| **B3** Marketing site | ✅ | ✅ | Plus mobile menu, navbar, find-your-clinic |
| **B4** In-clinic surfaces | 🟡 | 🟡 | Unblocked by A1 |
| **B5** Super-admin | ⬜ | ⬜ | Unblocked. `platform_admins` + `admin` guard exist |
| **B6** Telemetry | ⬜ | ⬜ | Not started |
| **B7** Compliance | 🟡 | 🟡 | **The isolation guarantee is now demonstrable rather than asserted** — the suite is the evidence a clinic's lawyer would want |

---

## 6. Page surface

| Tier | Built | Remaining |
|---|---|---|
| 1 — Marketing | 11 of 11 *(+ find-your-clinic)* | 0 |
| 2 — Auth & entry | 5 of 6 | **1** — invite acceptance, needs A2 tokens |
| 3A — Clinic app | 9 of 9 | 0 |
| 3B — Clinic settings | 0 of 8 | 8 |
| 3C — Super-admin | 0 of 7 | 7 |

**16 pages remain.** Unlike the last report, they are no longer *unbuildable* — A1
supplied the registry, per-tenant config surface and admin guard they all needed.

---

## 7. Open items and risks

### ⚠️ Hard gate before tenant #2 — the invite-code hole

Invite codes are still four global `.env` values shared across the entire install.
With one clinic that is merely untidy. **With two clinics, one code admits an admin to
whichever clinic they happen to visit** — a cross-tenant authentication hole.

**A2 must close this before A6 provisions a second clinic.** Not before tenant #10.

### ⚠️ The reserved-subdomain blocklist is not enforced

`config('tenancy.reserved_subdomains')` exists and is **read nowhere**.
ARCHITECTURE.md §8.5 requires it in sign-up validation *and* at provisioning. Until
then a clinic could claim `admin` or `api`. Belongs with A4.

### ⚠️ The scheduler's outermost alarm is unarmed

Silence detection is built and verified: `schedule:audit` exits non-zero and `/up`
returns 500 when tasks go quiet. But a silence detector that is itself silent detects
nothing — **catching a fully stopped scheduler requires an external monitor polling
`/up`**, which is a go-live deployment task, not code.

### Blocked on the Client, unchanged

- **Contabo Object Storage credentials** — backups remain local to the box. A lost VPS
  is a lost backup. Highest-risk item needing no engineering.
- **Paystack architecture** — nine questions, blocks B1.
- **SOW Appendix 1 pricing** — blank, while `/pricing` publishes the platform-spec
  figures. Now duplicated in `PlanSeeder` too; B2 should collapse them.

### Deployment state

The VPS smoke deploy is **deliberately behind `main`**, last deployed at `034c894`.
`africhart_smoke` still has a `users` table with those migrations recorded as run, so
the rename will not replay there. This is intended: **A6 tears that deploy down and
stands tenant #1 up fresh.** No reconciliation code was written for a database that
is about to be deleted.

---

## 8. Suggested sequence from here

1. **A2 — per-tenant configuration.** Invite codes first, as the hard gate above.
   Then `ACH-` prefix, consultation fee, catalogue scoping. The `settings` table is
   already in the architecture.
2. **A4 — `tenant:create`.** Mostly assembly: the create+migrate pipeline already runs
   and is exercised by every isolation test. Add idempotency, rollback, the reserved
   blocklist and an operator runbook.
3. **A6 — tenant #1 fresh**, plus a second throwaway clinic to prove isolation on the
   real box. Tear down the smoke deploy. → **Phase A acceptance.**
4. **B4 + invite acceptance** — settings hub and the last Tier-2 page.
5. **B1 + B2** — Paystack architecture agreed first.
6. **B5 + B6 + B7** → Phase B acceptance.

**Two things can start today and need no code:** chasing the Contabo credentials, and
working through the nine Paystack questions.

---

*Written 2026-08-25 against `11872c0`. Every figure was read from the repo or a live
run, not estimated. VPS state is last-known (`034c894`) and was not re-verified —
SSH agent unavailable at time of writing.*
