# Phase 2 — Progress Report #3

**Period:** 2026-08-26 → 2026-08-28 (continues [`PHASE2_PROGRESS_2026-08-25.md`](PHASE2_PROGRESS_2026-08-25.md))
**Repo at:** `ec2e9a9` on `main`, pushed · 7 commits · 60 files · +4,154 / −1,606
**Companions:** [`ARCHITECTURE.md`](ARCHITECTURE.md) · [`PHASE2_SOW_TODO.md`](PHASE2_SOW_TODO.md)

Legend: ✅ done · 🟡 partial · ⬜ not started · ⚠️ decision or input needed

---

## 1. Headline

**Both hard gates that report #2 raised against tenant #2 are closed, and A2 is
complete.** Report #2 named two blockers in its §7 and said a second clinic must not be
provisioned until they shut. Both are shut, and each was proved shut by performing the
attack it prevents and watching it fail.

| | |
|---|---|
| SOW items completed | **A2** — was 🟡, is now ✅ |
| Gates closed | Invite codes (security) · reserved subdomains (security) · identifier collision (correctness) |
| Test suite | **28 → 65 tests**, 143 → **337 assertions** |
| Blocked on the Client | Contabo credentials · Paystack decisions — both unchanged |
| Remaining before Phase A acceptance | **A4** provisioning · **A6** tenant #1 |

**A third gate was found that no checklist had named.** Two clinics were minting
*identical* patient, consultation and invoice identifiers — verified live, not inferred.
It is a collision rather than a leak, so it is a correctness gate rather than a security
one, but it had to close before clinic #2 for a reason worth stating: the fix gets more
expensive with every record created, because re-prefixing identifiers after the fact
changes IDs already printed on invoices and quoted in support calls. §4.1 has the
before-and-after.

---

## 2. Sprint 2, commit by commit

| # | Commit | What it delivered |
|---|---|---|
| 1 | `91d1040` | Client-confirmed pricing; the `plans` table made the single source of truth |
| 2 | `93c9609` | Three live 500s repaired — check-in and doctor assignment were broken in production |
| 3 | `cb60bb3` | Pre-tenancy schema dumps deleted; the setup docs they contradicted corrected |
| 4 | `091f895` | **Gate 1** — per-clinic staff invitations replace the global invite codes |
| 5 | `f14a639` | **Gate 2** — reserved-subdomain blocklist enforced at the model |
| 6 | `ec2e9a9` | **Gate 3** — per-clinic identifiers, fee and letterhead; A2 complete |

---

## 3. What the gates actually prove

Every claim below was established by attacking the system and watching the attack fail —
never by reading configuration and concluding it must be fine. Each guard was then
**deliberately broken** to confirm the test catches it, because a security test that has
never been seen to fail is an assertion, not evidence.

### 3.1 Gate 1 — invitations (`091f895`)

The four `REGISTER_CODE_*` values were process-wide while their effect was per-clinic:
one leaked admin code was an admin account at **every** clinic, present and future. A
second, independent hole sat in the same form — the visitor chose their own role from
buttons and `RegisterRequest` read it straight from the request, so whoever held the
admin code chose to *be* an admin.

Invitations now live in the **tenant** database, which makes cross-tenant rejection
structural: clinic A's row is not in clinic B's database, so A's token presented to B
finds nothing, exactly as an invented token does. There is no ownership comparison in the
controller to get wrong, drop, or miss on one of two paths.

**The proof.** With `staff_invitations` moved to the central database — the filing
mistake the design exists to prevent — the leak was performed and measured:

```
  A's invitation, presented to clinic B
  HTTP status from B ....... 302
  Redirected to ........... http://bravo.africhart.test/dashboard
  Staff in B before ....... 0
  Staff in B after ........ 1
  Row created in B ........ {"name":"Intruder","email":"newdoctor@alpha.test","role":"doctor"}
```

"Intruder" obtained a **doctor account at clinic B using clinic A's invitation**. The
headline test went red; restored, 13/13 green. The refusal is paired with a control
proving the same token still *works* at clinic A, so the refusal is isolation and not a
broken token.

Also proved: a tampered `role=admin` in the POST body is ignored (role comes from the
record), and all five failure modes — unknown, expired, used, revoked, wrong clinic —
return **byte-identical** pages, so nobody can probe which tokens exist.

The old mechanism was **deleted**, not disabled. A hole reachable by setting a config
value is a hole that reopens by accident.

### 3.2 Gate 2 — reserved subdomains (`f14a639`)

`config('tenancy.reserved_subdomains')` had shipped with ten labels in it since A1 and
was read by nothing. Demonstrated before fixing:

```
  Clinic::create(['subdomain' => 'api', ...])
  => CREATED clinic, db africhart_tenant_fda92cd7…, url https://api.<root>
     reserved list contains api? YES
```

Enforcement sits on the **`Clinic` model**, not in `tenant:create`. The architecture
nominates provisioning as the layer that matters, and the obvious reading would have been
wrong: seeders, tinker, the isolation suite and every future admin screen reach
`Clinic::create()` without touching a command. It guards `saving` rather than `creating`,
so renaming an existing clinic onto a reserved label is caught too — the worse case,
since that clinic already has staff and a bookmarked address.

Reserved is **derived, not just configured**: the config list plus every label sitting in
front of a central domain. `config/tenancy.php` already records that `central_domains` and
`root_domain` drifted once and left tenant routing unreachable; a static list beside a
derived one would reopen that class.

**The proof.** Neutering the guard turned three tests red: `www` was provisioned, an
existing clinic was renamed onto `api`, and a one-character subdomain was accepted.

### 3.3 Gate 3 — identifier collision (`ec2e9a9`)

See §4.1. Neutering it reported *"Both clinics generated the same patient identifier
[ACH-20260828-0001]"*.

---

## 4. What the work found

### 4.1 🔴 Two clinics minted identical identifiers

Three services hardcoded the prefix `ACH-` while the sequence counter was scoped to each
clinic's own database. The collision was therefore not a risk but a certainty: each clinic
counts from 1 in its own database, so their Nth record of the day lands on the same number.

```
BEFORE (two live tenants, same day)
  Hope Family Clinic     ACH-20260828-0001   ACH-C-20260828-0001   ACH-INV-20260828-0001
  Grace Medical Centre   ACH-20260828-0001   ACH-C-20260828-0001   ACH-INV-20260828-0001

AFTER
  Hope Family Clinic     HOPE-20260828-0001  HOPE-C-20260828-0001  HOPE-INV-20260828-0001
  Grace Medical Centre   GRAC-20260828-0001  GRAC-C-20260828-0001  GRAC-INV-20260828-0001
```

The prefix went to **central `clinics.id_prefix` behind a UNIQUE index**, deviating from
ARCHITECTURE §4.2 which had placed it in tenant settings. Distinctness across clinics
cannot be enforced from inside one clinic's database — two clinics could both choose
`ACH`, replacing a guaranteed collision with a hoped-for absence of one. Recorded as §12
item 6.

> It is the invitations argument inverted. There, the fact belonged in the tenant database
> because that made cross-tenant *reach* impossible. Here it belongs in central because
> that makes cross-tenant *collision* impossible. The rule in both: **store the fact where
> the constraint you need can actually exist.**

### 4.2 🔴 Three live 500s, in production, on the front desk's two main actions

The A1 rename left three validation rules naming the dropped `users` table as a **string**
— `unique:users,email`, and `Rule::exists('users','id')` twice. `users` no longer exists in
a tenant database, so each died with `SQLSTATE[42S02]` the moment the rule ran. **Queue
check-in with a doctor selected, and assigning a doctor to a waiting patient, had been
returning 500 since the rename.**

The rename's verification grep searched for `User::`, `UserRole`, `App\Models\User` and
`User $`. A table name inside a rule matches none of those — the same vocabulary gap that
hid `UserResource` in A1, but worse, because these fail at runtime rather than at boot.

Rules now reference `Staff::class`, which the model-name grep *would* catch. A repo-wide
sweep found six string-literal table references in total; the other three target tables
that exist.

### 4.3 🔴 Live credentials were being copied into page metadata

The shared `<head>` partial set `<link rel="canonical">` and `og:url` from
`url()->current()`. On `/invite/{token}` that copied a live, single-use secret into the two
fields a crawler indexes and a chat client reads to build a link preview.
**`/reset-password/{token}` had the identical defect** and was fixed in the same change
rather than left standing beside its cure.

Found because the identical-response test failed: the five failure modes differed, and the
difference was the token echoed back at them.

### 4.4 🟠 Single-use was not atomic

The first acceptance implementation checked, then wrote. Two requests carrying the same
token could both pass the check. The unique index on `staff.email` would have stopped the
second account — but that is a different table's constraint catching this one's race, and
it surfaces as a 500 rather than a refusal. Now re-read inside the transaction under
`lockForUpdate()`.

### 4.5 🟠 Deleting `/register` would have locked clinic #2 out entirely

Invitations are sent *by* an admin, and a freshly provisioned clinic has none.
`tenant:create` is A4 and unbuilt; the only existing path to a first admin is the demo
seeder that must never touch a real clinic. Closing the security gate would have bricked
the front door.

`php artisan clinic:invite <subdomain> <email> --role=admin` issues the first invitation
from the CLI, by an operator who already controls the database. It always prints the link
as well as mailing it — a new clinic is exactly where mail is most likely unconfigured, and
nothing stores the raw token to recover later. **A4's `tenant:create` should call this as
its last provisioning step.**

### 4.6 🟠 The formatter broke the test suite's safety guard

Pint's `php_unit_method_casing` fixer treats any method whose name begins with "test" as a
test case. It renamed `TenancyTestCase::testPrefix()` — a helper — to `test_prefix()` and
rewrote the declaration **without its four call sites**, breaking the class outright. One of
those call sites is the guard in `dropDatabase()` that stops the suite deleting any database
lacking the test prefix, so the failure was not confined to a red test run. Renamed to
`tenantDatabasePrefix()`, with a note, so the fixer cannot target it again.

### 4.7 Things that were already correct, verified rather than assumed

- **The drug catalogue.** The checklist called it 🟡 "needs tenant scoping". `medications`
  has been in the tenant migration set all along and is absent from central — each clinic
  already had its own list at its own prices. Confirmed against both live databases and
  pinned with a test rather than rebuilt.
- **The brand-string sweep**, in part. The app shell, sidebar and API title say "AfriChart"
  and should: AfriChart is the vendor. Only *clinic* identity needed to become per-tenant.

### 4.8 Two false signals caught before they became conclusions

- A probe printed **PASS for two cases while validating nothing** — keyed on `doctor_id`
  when the field is `assigned_doctor_id`, so `$rules[$field]` was undefined and Laravel
  validated an empty ruleset. Caught on a PHP warning.
- A test failure reading `Database connection [tenant] not configured` looked like a tenancy
  defect. It was an attribute read *outside* the tenant closure, where the lazy datetime cast
  goes looking for a purged connection.
- A run showing one failure that vanished on retry was **two phpunit processes sharing one
  test database**, whose teardown drops by prefix. Killed and re-run serially rather than
  retried until green.

---

## 5. Corrected SOW status

### Phase A

| Item | Status | Where it stands |
|---|---|---|
| **A1** Tenancy | ✅ | Complete, accepted at report #2 |
| **A2** Per-tenant config | ✅ | **Complete this sprint.** Invitations, ID prefix, fee, catalogue, clinic letterhead |
| **A3** Infrastructure | 🟡 ~90% | Only off-site object storage outstanding — needs credentials, not code |
| **A4** Provisioning | ⬜ | `tenant:create` not built. The create+migrate pipeline it wraps already runs and is exercised by all 65 tests. The reserved blocklist it was to enforce is **already done**, at a better layer |
| **A5** Backups | 🟡 | Live, encrypted, per-tenant, restore rehearsed. Off-site destination outstanding |
| **A6** Tenant #1 | ⬜ | Fresh stand-up. Now genuinely unblocked |

### Phase B

| Item | Status | Where it stands |
|---|---|---|
| **B1** Subscription & billing | ⬜ ⚠️ | Nine Paystack questions still open |
| **B2** Plans, gating, metering | 🟡 | Pricing is now single-source in the `plans` table; nothing enforced |
| **B3** Marketing site | ✅ | Complete |
| **B4** In-clinic surfaces | 🟡 | **Advanced.** The tenant `settings` table now exists — the Settings hub's backing store. `/staff` gained invitation management. Wizard, hub, plan visibility and billing-state screens remain |
| **B5** Super-admin panel | ⬜ | 7 pages |
| **B6** Telemetry | ⬜ | Not started |
| **B7** Compliance | 🟡 | Isolation guarantee now demonstrable in four dimensions plus invitations |

---

## 6. Page surface

| Tier | Built | Remaining |
|---|---|---|
| 1 — Marketing | 11 of 11 | 0 |
| 2 — Auth & entry | **6 of 6** *(invite acceptance built)* | **0** |
| 3A — Clinic app | 9 of 9 | 0 |
| 3B — Clinic settings | 0 of 8 | 8 |
| 3C — Super-admin | 0 of 7 | 7 |

**15 pages remain**, all in the two unstarted tiers.

---

## 7. Test suite

| Suite | Tests | Guards |
|---|---|---|
| `ConnectionBoundaryTest` | 4 | Central/tenant connection separation |
| `DataIsolationTest` | 5 | §6.1 — no query in A returns B's records |
| `SessionIsolationTest` | 6 | §6.2 — a session on A is not valid on B |
| `CacheIsolationTest` | 6 | §6.3 — cache keys do not cross |
| `QueueIsolationTest` | 7 | §6.4 — jobs execute against their own tenant |
| `QueueFlowTest` | 4 | Check-in and doctor assignment (the 500s of §4.2) |
| `InviteIsolationTest` | 13 | Gate 1 |
| `ReservedSubdomainTest` | 10 | Gate 2 |
| `PerTenantConfigTest` | 10 | Gate 3, settings isolation, catalogue isolation |
| **Total** | **65** | 337 assertions, real MySQL, two real provisioned tenants |

⚠️ **`php artisan test` still exercises none of this.** `phpunit.xml` registers only
`tests/Unit` and `tests/Feature`, which hold Laravel's two stock example tests. The real
suite needs `composer test:tenancy`, because it needs real MySQL. The default command
reports green having tested nothing — a trap for anyone, including CI, who assumes
otherwise. Recorded in the to-do's new §5.1.

---

## 8. Open items and risks

### ✅ Closed since report #2

Both §7 gates from the last report — the invite-code hole and the unenforced
reserved-subdomain blocklist — are closed and proved closed.

### ⚠️ The scheduler's outermost alarm is still unarmed

Unchanged from report #2, and unaddressed this sprint. Silence detection is built and
verified; catching a *fully stopped* scheduler requires an external monitor polling `/up`.
That is a go-live deployment task, not code.

### ⚠️ Blocked on the Client, unchanged

- **Contabo Object Storage credentials** — backups remain local to the box. **A lost VPS is
  a lost backup.** Still the highest-risk open item, and it needs no engineering.
- **Paystack architecture** — nine questions at §6 of the to-do; blocks B1.

### ⚠️ Not yet examined

- **A3's remaining items** — the central-vs-tenant MySQL user/permission model, and
  scripting the deploy. Neither has been investigated; the checklist's word on them is not
  evidence either way, which this sprint twice found to matter.

### Deployment state

The VPS smoke deploy is **deliberately behind `main`** and was not touched this sprint.
Report #2's account stands, and **A6 tears it down** rather than reconciling it. Note that
none of Sprint 2's work is on the server: the gates are closed in the repo, not in the
running deployment.

---

## 9. Suggested sequence from here

1. **A4 — `tenant:create`.** Mostly assembly now: the create+migrate pipeline runs, the
   reserved blocklist is enforced at a layer the command cannot bypass, and
   `clinic:invite` supplies the first admin. Add idempotency, rollback on partial failure,
   and an operator runbook — then have it call `clinic:invite` as its final step.
2. **A6 — tenant #1 fresh**, plus a second throwaway clinic to prove isolation on the real
   box. Tear down the smoke deploy. → **Phase A acceptance.**
3. **B4 Settings hub** — the `settings` table and `/staff` invitations are already the
   backing store for two of its five sections.
4. **B1 + B2** — Paystack architecture agreed first.
5. **B5 + B6 + B7** → Phase B acceptance.

**Off the critical path, cheap, and worth doing before either:** point `php artisan test`
at the real suite (§7), and take the Contabo credentials off the Client's plate — it is one
`.env` block away from removing the project's largest standing risk.

---

*Written 2026-08-28 against `ec2e9a9`. Every figure was read from the repo or a live run.
The before/after identifier tables and the breach transcript in §3.1 are verbatim command
output, not reconstructions.*
