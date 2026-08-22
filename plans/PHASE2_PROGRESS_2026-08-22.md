# Phase 2 — Progress Report

**Period:** 2026-08-21 → 2026-08-22 (two working days)
**Repo at:** `5a7000a`, branch `feature/marketing-site` (26 commits ahead of `main`)
**Companion:** [`PHASE2_SOW_TODO.md`](PHASE2_SOW_TODO.md) — the living checklist. This document is the
point-in-time report; corrections it identifies should be folded back into that file.

Legend: ✅ done · 🟡 partial · ⬜ not started · ⚠️ decision or input needed

---

## 1. Headline

Two days produced **one complete SOW line item (B3) and the whole of Sprint 0**. Everything else in
Phase A remains gated on **A1 tenancy**, which has not started and is still the long pole.

| | |
|---|---|
| SOW items completed | **B3** (public marketing site) — was ⬜, is now ✅ |
| SOW items advanced | A3 → ~90%, A5 → live/encrypted/rehearsed |
| Commits | 26 on `feature/marketing-site`, 55 files, +4,660 lines |
| Blocked on the Client | Off-site backup storage (Contabo Object Storage credentials) |
| Blocked on a decision | Paystack architecture (§6 of the to-do) |

---

## 2. Corrections to `PHASE2_SOW_TODO.md`

The to-do was written on 2026-08-21 and is now wrong in four places. Listed first because a stale
checklist is worse than no checklist.

| Where | Says | Reality |
|---|---|---|
| §1 scope map, **B3** | ⬜ "Page map exists; no views" | ✅ **Complete.** 12 marketing views, 10 routed pages, three legal documents, a design system and a motion scale |
| §9 Sprint 4 | Pairs **B3 + B4** as one sprint | B3 is done and B4 is untouched. Sprint 4 is now B4 alone, and it moves *after* A1 because the Settings hub needs per-tenant config to edit |
| §8, note 1 | `fix/sprint-0-hardening` "committed locally but not pushed" | **No longer true.** `9a52dd1` is an ancestor of `5a7000a`, which is on GitHub. Sprint 0 is safe |
| §8, note 1 | Server checkout shows 4 modified files | **Still true and still unreconciled.** The server was given those files by direct copy; it has not pulled |

**Also worth adding to §1:** B7 compliance is no longer entirely ⬜. The three legal documents are
written (Privacy, Terms, and a per-clinic DPA), which is most of B7's first two bullets — though all
three carry a visible "pending professional legal review" notice, and certification remains the
Client's responsibility under SOW §9.3.

---

## 3. What shipped

### 3.1 Sprint 0 — deploy hardening (2026-08-21, `10721b2`)

Recorded in full at §2 of the to-do; summarised here for completeness. Three defects, each verified
by live test rather than by reading config:

- **A blank `BACKUP_NOTIFICATION_EMAIL` took the whole app down.** `env()` returns `''`, not `null`,
  so the fallback never fired and spatie validated at boot — meaning the value `.env.example`
  shipped killed every request.
- **`config:cache` could never run**, because `config/l5-swagger.php` instantiated an object that
  `var_export()` cannot serialise.
- **`trustProxies(at: '*')` let any client forge its own audit-log IP** — demonstrated, not
  theorised. For a product sold on its audit trail that is an integrity defect.

Plus: the task scheduler had **never executed once** (so backups had never run despite SOW §2
listing them as a completed pre-condition), backups are now AES-256 with a rehearsed restore across
all nine tables, PHP-FPM was sized against measured memory, and wildcard subdomain routing was found
to be working *by accident* and made explicit.

> One methodological note worth carrying forward: the first encryption check "passed" only because
> `unzip` was not installed. **Never accept a security check that passes because a tool is missing.**

### 3.2 B3 — public marketing site (2026-08-22, `379f6ca` → `5a7000a`)

Ten Tier-1 pages, built to the page inventory:

| Page | Notes |
|---|---|
| Home | Full-viewport hero, five-role flow, sticky feature showcase, data-isolation block, differentiator panel, pricing teaser, FAQ |
| Features | Eight anchored feature blocks across three alternating layouts |
| Pricing | Three tiers, setup fee stated plainly, 20-row comparison table, eight-question FAQ |
| About | Origin, values, local-support promise, conditional team block |
| **Contact** *(new)* | Channels beside the form, email with copy-to-clipboard |
| Book a demo | Focus layout, 2-step form, process strip, agenda, trust row, objection FAQ |
| Get started | Focus layout, 3-step form, plan carried from Pricing, live subdomain preview, consent |
| Privacy · Terms · Data-processing | Structured NDPA documents, not filler |

Supporting work: General Sans self-hosted (ending a visible load-time font swap), a three-tier
motion scale with a reduced-motion guard, `marketing_leads` capture with honeypot and throttling,
and a shared `<head>` partial now used by all three layouts.

### 3.3 Tier 2 — auth pages (2026-08-22, `5a7000a`)

Login, password reset and email verification brought onto a shared auth shell. One item was a **fix,
not a restyle**: login used `throttle:5,1`, which returns Laravel's raw 429 page — the "too many
attempts" message the inventory asks for could never have appeared. It is a named limiter now, keyed
on email *and* IP so one person's lockout cannot lock out a colleague behind the same NAT.

---

## 4. Corrected status — every SOW item

### Phase A

| Item | Status | Where it stands |
|---|---|---|
| **A1** Tenancy | ⬜ | Zero tenancy packages in `composer.json`. Not started. **The long pole** |
| **A2** Per-tenant config | 🟡 | Verified still global: `ACH-` prefix hardcoded in three services (`PatientService.php:115`, `ConsultationService.php:87`, `InvoiceService.php:167`); fee from `config/billing.php`; invite codes from four `.env` values, one per role, never expiring. Drug catalogue is DB-backed but not tenant-scoped |
| **A3** Infrastructure | 🟡 ~90% | Only off-site object storage outstanding. `config/backup.php` already has the `s3` destination wired — it needs credentials, not code |
| **A4** Provisioning | ⬜ | Depends on A1 |
| **A5** Backups | 🟡 | Live, encrypted, restore rehearsed. Per-tenant backups and off-site destination outstanding |
| **A6** Tenant #1 | ⬜ | A fresh stand-up, not a migration |

### Phase B

| Item | Status | Where it stands |
|---|---|---|
| **B1** Subscription & billing | ⬜ ⚠️ | Architecture undecided — nine open questions at §6 of the to-do |
| **B2** Plans, gating, metering | ⬜ | Tiers are designed and now published on `/pricing`; nothing is enforced |
| **B3** Marketing site | ✅ | **Complete.** See §3.2 |
| **B4** In-clinic surfaces | 🟡 | `/drug-catalog` and `/staff` exist and fold in. Wizard, Settings hub, plan visibility and billing-state screens absent — **8 pages** |
| **B5** Super-admin panel | ⬜ | **7 pages.** Depends on A1 + A4 |
| **B6** Telemetry | ⬜ | Not started |
| **B7** Compliance | 🟡 | Legal documents written; isolation guarantee and breach-response plan not |

---

## 5. Page surface — what is left

| Tier | Built | Remaining |
|---|---|---|
| **1 — Marketing** | 10 of 10 | **0** (blog deferred by the inventory) |
| **2 — Auth & entry** | 4 of 6 | **2** — find-your-clinic, invite acceptance |
| **3A — Clinic app** | 9 of 9 | 0 |
| **3B — Clinic settings** | 0 of 8 | **8** |
| **3C — Super-admin** | 0 of 7 | **7** |

**17 pages remain, and not one is buildable today.** Each needs something A1 provides: a clinic
registry to look up, per-tenant config to edit, or tenants to administer. That is the case for
starting A1 next rather than continuing to build UI.

---

## 6. Blocked, and on whom

| Blocker | Owner | Cost of delay |
|---|---|---|
| **Contabo Object Storage credentials** | **Client** | Backups are local to the box. A lost VPS is a lost backup. This is the single highest-risk open item and it needs no engineering |
| **Paystack architecture** (§6) | **Joint decision** | Blocks B1, and B2 partly. Nine questions — subscription mechanism, setup-fee handling, trial mechanics, Group-tier metering, read-only propagation |
| **SOW Appendix 1 pricing** | **Client** | Appendix is blank. `/pricing` currently publishes the platform-spec proposal (₦25k / ₦50k / ₦40k-per-site). **These are live on a public page — they should be confirmed or corrected** |
| **Architecture doc rewrite** | Developer | A1 depends on it; §7 lists what has drifted |

---

## 7. Two decisions still recorded as open

**Redis vs `database` drivers under tenancy** (to-do §7). The architecture doc assumes Redis; the
deploy uses `database` for cache, sessions and queue. This is not merely a mistake to correct — with
`database`, isolation is automatic and structural, which is the same argument that justifies
DB-per-tenant in the first place. With Redis, isolation depends on correct prefixing and a bug is a
cross-tenant leak. **Recommendation stands: stay on `database` through Stage 1–2.**

**`SESSION_DOMAIN` must stay unset.** If it is ever set to `.africhartemr.com`, one authenticated
session is presented to every clinic subdomain. This belongs in the A1 isolation test suite as an
explicit assertion, not an assumption.

---

## 8. Suggested sequence from here

Revised from §9 of the to-do, since B3 is done.

1. **Sprint 1 — A1 tenancy.** Rewrite the architecture doc → central registry → subdomain
   identification → per-request switching → migration split → **isolation tests** (data, session,
   cache, queue). Do not compress the tests; they are what the commercial proposition rests on.
2. **Sprint 2 — A2 + A3 remainder + A4.** Per-tenant config → off-site backups *(unblocks the
   moment credentials arrive — can be pulled forward)* → `tenant:create`.
3. **Sprint 3 — A6 → Phase A acceptance.** Tenant #1, a second throwaway clinic, prove isolation,
   per-tenant backup and restore, tear down the smoke deploy.
4. **Sprint 4 — B4 + the two remaining Tier-2 pages.** Setup wizard and Settings hub; find-your-clinic
   and invite acceptance become buildable here because A1/A2 have landed.
5. **Sprint 5 — B1 + B2.** Paystack architecture agreed first, then subscriptions, webhooks,
   dunning, gating, metering.
6. **Sprint 6 — B5 + B6 + B7 → Phase B acceptance.**

**Two things can start immediately and in parallel with A1:** chasing the Contabo credentials, and
working through the nine Paystack questions. Neither needs code.

---

## 9. Housekeeping

- `origin` is the SSH URL and `feature/marketing-site` is on GitHub at `5a7000a`. Local
  remote-tracking refs are absent because pushes were made by URL — one `git fetch origin` fixes it.
- `fix/sprint-0-hardening` and `feature/a1-critical-fixes` have no separate remote branches, but
  both are ancestors of branches that do. Nothing is at risk.
- **The server checkout is still unreconciled with git** — four PHP files were copied directly during
  Sprint 0 verification. Pull on the server before the next deploy.
- `feature/marketing-site` is 26 commits ahead of `main` and has not been merged.

---

*Written 2026-08-22. Repo at `5a7000a`. VPS state as recorded on 2026-08-21 and not re-verified since.*
