# AfriChart EMR — Platform Spec: System Fixes, Public UI & Subscription Plans

**Purpose:** the product-side companion to the SaaS architecture doc. This covers (1) the fixes that take the MVP from solid to exquisite, (2) the public-facing UI mapped page by page, and (3) the subscription/upgrade plans. The architecture doc says *how it scales*; this says *what the clinic and the prospect actually see and use*.

**Design language (carry through everywhere, marketing site included):** General Sans typeface; warm-off-white + near-black palette (`#ffffff` page, `#f8f7f5` warm surface, `#1a1a1a` ink, `#636363` muted, `#ececec` hairline, `#c2001d` accent); 8px rounded corners; dark pill buttons; **no shadows** — depth from background contrast. The marketing site and the app should feel like one continuous product so the demo → signup → in-app journey never visually breaks.

---

## Implementation status (updated 2026-06-17)

Legend: ✅ done · 🟡 partial / seam in place · ⬜ not started.

- **Part A1 (critical fixes): ✅ all six shipped** on branch `feature/a1-critical-fixes` (single-tenant — built for the current shared DB as "clinic #1"; multi-tenancy deferred). Plus A3-16 (auth throttle) ✅.
- A2 (Paystack, partial payments, EOD email, global search, thermal receipt): ⬜ Stage 2.
- A3 (mobile cards, modal a11y, empty states, polling back-off): ⬜ except 16 ✅.
- Part B (marketing site, settings hub, billing screens, super-admin): ⬜ — though the A1 work left two in-app **seams**: a minimal admin **Drug Catalog** page (B2-§12) and a minimal **Staff deactivate/reactivate** page (B2-§12 Team & Seats) 🟡.
- Part C (subscription plans), Part D Stages 2–4: ⬜.

Per-item status is tagged inline below.

---

# PART A — SYSTEM FIXES (solid → exquisite)

Grouped by priority. The first group decides whether staff are still using AfriChart in week 6 — it comes before any new clinical module or any scaling work.

## A1. Critical — the daily-friction and trust fixes (do first)

**1. Vitals at check-in (the vitals issue).** ✅ DONE — vitals columns on `patient_queue`, `PatientQueueService::recordVitals()`, `startConsultation()` absorbs them; queue-row heartbeat button → `queueVitals()` modal + "✓ vitals" marker.
*Today:* vitals can only be recorded inside an open consultation — but in a real clinic the nurse takes vitals *before* the doctor opens anything, while the patient waits. The current model forces the doctor to open the consultation early (polluting his queue state) or makes vitals wait for him. It contradicts the actual 8:45-AM nurse step.
*Fix:* let vitals attach to the **queue entry**. The nurse records Temp/BP/Pulse/Weight/Height (BMI auto-computed) while the patient is still `Waiting`. When the doctor starts the consultation, it **absorbs** the vitals already taken. Technically: vitals fields/table linked to the queue entry, nullable; `PatientQueueService` gains a `recordVitals()` step; `ConsultationService::startConsultation()` pulls them forward. Nurses keep their existing permission; no new role logic.
*Cheap validation first:* watch one real nurse work one morning before finalizing the flow — two hours that confirm or kill the design.

**2. Drug price catalog.** ✅ DONE — `medications` DB table + `Medication` model + seeder (from `medications.json`, with prices); `InvoiceService` pre-fills med lines from `default_price` (was ₦0, still editable); autocomplete reads the DB; admin `/drug-catalog` CRUD.
*Today:* prescriptions autocomplete from `medications.json`, but invoices price every drug at ₦0 — reception re-types the same Paracetamol price on every invoice, forever. This is the single highest-probability cause of "paper was faster."
*Fix:* promote the drug list to a per-clinic DB table with a `default_price`; the invoice pre-fills it (still editable inline), and optionally remembers the last price used. Per-clinic because prices vary by clinic (and this aligns with the per-tenant config move in the architecture doc).

**3. Automated backups + a tested restore.** ✅ DONE — `spatie/laravel-backup` (DB + `storage/app`), scheduled daily in `routes/console.php` (run/clean/monitor), off-site S3-compatible disk via `BACKUP_OFFSITE_DISK`; runbook + rehearsed-restore steps in `docs/BACKUPS_AND_OPS.md`.
*Today:* not mentioned anywhere in 49 sections of the walkthrough.
*Fix:* scheduled per-clinic `mysqldump` to offsite object storage, plus at least one rehearsed restore. For a system whose pitch is "your records, safe forever," this is feature #0.

**4. Queued email (perceived speed).** ✅ DONE — `EmailVerificationCode` + `AdminActivity` now `ShouldQueue`; `QUEUE_CONNECTION=database` (no Redis dependency for clinic #1). ⚠️ requires a running worker (`queue:work`) — documented in `docs/BACKUPS_AND_OPS.md`.
*Today:* registration/verification/patient actions send SMTP synchronously, freezing the front desk 1–2s. Spinners mask it but front-desk speed *is* the product.
*Fix:* move mail to the queue (Redis + Supervisor worker, per the VPS stack). Actions return instantly.

**5. Fetch-failure handling on the modals.** ✅ DONE (patient modal) — explicit "NOT saved" toast on network error, 419 → session-expired reload, and an `X-Idempotency-Key` with server-side dedup to kill duplicates.
*Today:* unclear what the patient modal shows on network timeout / offline / expired CSRF. In a Nigerian clinic, network blips are a *when*, not an *if*. Silent failure → the receptionist doesn't know if the patient saved → duplicates or lost registrations.
*Fix:* catch fetch errors → explicit toast ("Network problem — patient NOT saved, try again"); handle the 419 (expired token) by prompting a refresh; add an idempotency key on patient-create to kill duplicates.

**6. Soft-delete patients (and staff).** ✅ DONE — `SoftDeletes` on `Patient` + `User`; patient Archive/Restore (admin) with Active/Archived toggle; `/staff` deactivate/reactivate (admin, self-deactivation blocked); deactivated users can't authenticate. Reassigning a departed doctor's open consultations deferred to B2-§12.
*Today:* patient delete is destructive and cascades to consultations/invoices.
*Fix:* soft-deletes on `Patient` (and `User`); block hard delete when records exist; offer "Archive" instead. Medical records must be undeletable in practice — one accident destroys trust permanently.

## A2. High impact — adoption & revenue

**7. Online patient payments (Paystack).** Today billing is cash/manual only — a gap even at one clinic. Add Paystack for patient invoice payment (card/transfer/USSD), recorded against the invoice alongside the existing cash flow.

**8. Partial payments / deposits.** Nigerian clinics routinely take a deposit before treatment and a balance after. An invoice that's only Draft → Paid can't represent this, so staff track the truth on paper — which quietly destroys the audit log's integrity, the feature you sell on. Add payment records against an invoice (amount, method, time) with a running balance; an invoice is Paid only when the balance hits zero.

**9. End-of-day owner summary email.** A scheduled per-clinic email to the owner: "Today: 14 patients, 9 consultations, ₦86,500 collected." The cheapest retention *and* referral artifact you can build — it pulls the absent owner back daily and is the thing he forwards to clinic-owner friends.

**10. Global patient search.** A topbar omnisearch (name / phone / `ACH-` ID, keyboard shortcut `/`) reachable from any page — every role starts most tasks with "find the patient," and today that means navigating to the Patients page first.

**11. Thermal 80mm receipt.** Nigerian clinics hand patients thermal receipts, not A4. Add an 80mm print stylesheet variant of the invoice (pure CSS, no new infra). The receipt is the most patient-visible artifact of the whole system.

## A3. Polish — accessibility, mobile, resilience

**12. Mobile card layouts** for the queue and patient tables (the two phone-bound views: the nurse and the owner). Tables that only scroll horizontally are rough on a phone.
**13. Modal accessibility** — focus trap, `Esc` to close, `aria-modal`, focus return; and text labels inside status badges (not color alone — you already have `label()`, render it).
**14. Empty/first-run states** — every new clinic starts empty; each empty screen should name the next action ("No patients yet — Register your first patient"). Do not seed a real tenant with demo data (the seeder stays a *sales* asset only).
**15. Polling back-off** — after N unchanged polls, widen the interval (8s → 30s) to cut load on shared infra; pausing on hidden tabs is already done.
**16. Rate-limit auth + the 6-digit verification endpoint** — codes are brute-forceable; confirm Laravel's throttle is on those routes. ✅ DONE — `throttle:5,1` added to web + API `/login`; the verify/resend endpoints already had `throttle:6,1`.

---

# PART B — PUBLIC UI MAP (page by page)

Two surfaces: the **marketing site** on the root domain (`africhartemr.com`), and the **in-app account surfaces** inside each clinic's subdomain. The marketing site is where strangers become trials; the in-app surfaces are where a clinic configures, pays, and upgrades.

## B1. Marketing site (root domain)

### 1. Home / Landing — `africhartemr.com`
The single most important page; it carries the wedge.
- **Hero:** headline on the owner-oversight promise — *"See every patient, every prescription, and every naira in your clinic — from anywhere."* Subhead naming the audience (Nigerian private clinics). Primary CTA **Book a Demo** / **Start Free Trial**; secondary **See Pricing**. Visual: a clean shot of the **patient timeline** or the **owner dashboard** (the two screens that sell it).
- **Trust strip:** "Built for Nigerian clinics · Naira pricing · Your data stays isolated" + the AfriChart reference logo/quote once permissioned.
- **How it works:** the four-role workflow as a simple flow — Receptionist checks in → Nurse takes vitals → Doctor consults & prescribes → Reception bills → Owner sees it all. This is your §40–45 story compressed into one scannable band.
- **Feature highlights (3–4 cards):** Live queue · Consultation + prescriptions · Auto-totaling invoices + receipts · Audit log + owner dashboard. Each one line, each linking to the Features page.
- **The differentiator block:** "Runs even when Lagos software companies won't pick up — local, naira-priced, your data on isolated infrastructure." Speak to the fears the business analysis surfaced (revenue leakage, staff trust, local support).
- **Pricing teaser:** three tiers at a glance → link to Pricing.
- **Closing CTA + footer** (links to Features, Pricing, About, Contact, Login, Privacy, Terms).

### 2. Features — `/features`
Depth for the evaluator. Sections mirroring the workflow: Patient records & timeline · Live queue · Consultations & vitals · Prescriptions (autocomplete) · Billing, receipts & payments · Audit trail & oversight dashboard · Roles & permissions · REST API (for groups/integrators). Each with a screenshot and 2–3 sentences. End with a CTA.

### 3. Pricing — `/pricing`
The conversion page. The three tiers as cards (Part C), naira-prominent, with the **setup fee** stated honestly, a feature-comparison table, and a short FAQ (What's the setup fee for? Can I change plans? What happens if I stop paying? — answer: read-only, never deleted. Is my data isolated? — yes, your own database). Primary CTA per card: **Start Trial** / **Talk to us** for Group.

### 4. About — `/about`
The origin story is a genuine asset: *a Port Harcourt developer built a clinic's system so well that other clinics started asking for it.* True, regional-pride-activating, and it pre-empts the "can I trust a young founder" objection by leading with a real deployment. Mission + the local-support promise.

### 5. Book a Demo / Contact — `/demo`
Lead capture: clinic name, owner name, phone (WhatsApp), city, number of doctors. WhatsApp click-to-chat as the primary channel (this demographic lives there). This form is the top of your sales funnel — every field feeds the prospect workbook.

### 6. Login — `/login` (root)
A "find your clinic" entry: the staff member types their clinic's subdomain (or you link them straight to `theirclinic.africhartemr.com/login`). Keep it dead simple; most staff bookmark their subdomain after first login.

### 7. Get Started / Clinic Signup — `/signup`
Stage 1–2: a request-access form that triggers manual provisioning. Stage 3: full self-serve → payment → auto-provision (per the architecture roadmap). Same visual language as login.

### 8. Legal — `/privacy`, `/terms`, `/data-processing`
Privacy policy, terms, and an NDPR/NDPA data-processing statement. Not optional once you collect patient data; also a *sales* reassurance for bigger clinics. *(Get these reviewed — not legal advice.)*

### 9. (Later) Resources / Blog — `/resources`
For the SEO play the competitive analysis flagged ("clinic management software Nigeria," "how to stop revenue leakage in your clinic"). Defer until post-PMF.

## B2. In-app account surfaces (inside each clinic's subdomain)

These mostly don't exist yet and are the bridge between "an app" and "a SaaS product a clinic self-manages."

### 10. Login — `clinicX.africhartemr.com/login`
The existing login, now per-subdomain, optionally showing the clinic's own logo/name (per-tenant branding) so staff land somewhere that feels like *theirs*.

### 11. Setup wizard (first run)
The new clinic admin's first session must walk: **Clinic profile** (name, logo, address) → **Consultation fee** → **Drug catalog & prices** → **Invite staff** → **Register first patient**. A checklist on the admin dashboard tracks completion. This is the highest-leverage onboarding investment and mostly UI over config that will already be per-tenant.

### 12. Settings hub
A new top-level area, admin-gated:
- **Clinic Profile** — name, logo, address, ID prefix, contact.
- **Billing & Plan** — current plan, usage ("6 staff · audit log included"), invoices/receipts for the clinic's own subscription payments, upgrade/downgrade, payment method. Reads subscription state from the central registry.
- **Team & Seats** — invite staff (per-clinic, single-use, expiring codes/links — replacing the `.env` codes), deactivate (soft-delete) staff, reassign a departed doctor's open consultations, seat count vs plan limit. 🟡 deactivate/reactivate already shipped at `/staff` (A1-6); invites, reassignment, and seat limits still ⬜.
- **Branding** — logo + clinic name shown in topbar, on invoices/receipts (already config-driven; surface it).
- **Drug Catalog** — manage the per-clinic medication list + default prices (feeds fix A1-2). 🟡 minimal admin CRUD already shipped at `/drug-catalog` (A1-2); folding it into the Settings hub still ⬜.

### 13. Plan & usage visibility / upgrade prompts
In-product surfacing of "you're on Starter · audit log is a Clinic-plan feature" with a contextual **Upgrade** path — so the gated audit-log/dashboard becomes a felt upgrade lever, not a hidden wall.

### 14. Billing-state screens
Trial-ending banner; payment-failed notice with retry; **read-only lockout** screen (data visible, writes disabled, clear "settle your subscription to resume" message — honest and recoverable, never punitive, patient data never hidden or deleted).

### 15. Super-admin panel (central — you, not the clinic)
Operational cockpit: list all clinics + status, provision/suspend, impersonate-for-support (audited), platform metrics. Detailed in the architecture doc; noted here so the UI surface is accounted for. Visually it can be plainer — it's an internal tool.

---

# PART C — SUBSCRIPTION & UPGRADE PLANS

Naira-native, owner-framed, with the audit log/dashboard as the deliberate upgrade lever (the *owner's* feature lives one tier up from the *receptionist's* basics).

| | **Starter** | **Clinic** (anchor) | **Group** |
|---|---|---|---|
| **Price** | ₦25,000/mo | ₦50,000/mo | ₦40,000/mo **per site** (2+ sites) |
| **Setup fee** (one-time) | ₦50,000 | ₦75,000 | ₦100,000+ (per group) |
| Sites | 1 | 1 | 2+ |
| Staff | Up to 3 | Unlimited | Unlimited |
| Patients, queue, vitals | ✓ | ✓ | ✓ |
| Consultations & prescriptions | ✓ | ✓ | ✓ |
| Invoicing, receipts, payments | ✓ | ✓ | ✓ |
| **Audit log & oversight dashboard** | — | **✓** | ✓ |
| PDF/thermal receipts, CSV export | — | ✓ | ✓ |
| Consolidated multi-site owner dashboard | — | — | ✓ |
| REST API access | — | — | ✓ |
| Support | Email | WhatsApp | Priority + onboarding |

**Why this shape:**
- **Starter** lands the fearful first-timer cheaply and *deliberately omits the audit log* — the feature the owner most wants — creating the upgrade pull.
- **Clinic** is the anchor: the audit log + dashboard live here, which is what converts the owner from "records" to "control."
- **Group** monetizes your best customers' growth and is where the subdomain-per-site model and the API earn their keep.
- **Setup fee** filters unserious buyers and funds acquisition; discount it for annual prepay rather than waiving it (and hold a reserve on annual prepays — you're servicing 12 months on cash collected once).

**Trial & risk reversal:** setup-fee first, then 30 days free, gated on *daily usage* not just calendar. "If your staff isn't using it daily by day 30, full setup-fee refund." The fee commits them; the refund de-risks them; the daily-usage condition forces real onboarding.

**Expansion path (where SaaS economics compound):** Starter → Clinic (audit-log envy) → Group (second site) → metered/gated **add-ons**: drug inventory (+₦15k, first paid add-on), SMS reminders (usage-metered via Termii), HMO/NHIA claims pack (+₦20k), AI consultation summaries (+₦10k, your Claude-API edge). A ₦25k clinic becomes a ₦120k+ clinic with no new logo.

---

# PART D — SEQUENCING (how this lands against the architecture roadmap)

- **Before/alongside Stage 1 (Foundation):** Part A1 fixes (vitals, drug prices, backups, queued mail, fetch-failures, soft-delete) ✅ **done** (single-tenant) + the in-app invite/branding/drug-catalog settings that the per-tenant config move requires anyway 🟡 (drug-catalog + staff-deactivate seams done; invites/branding ⬜). Marketing site v1 (Home, Features, Pricing, About, Demo, Login, Legal) ⬜. Make clinic #1 exquisite.
- **Stage 2 (Monetization):** Billing & Plan settings, plan/usage visibility, billing-state screens, online patient payments (A2-7), partial payments (A2-8), end-of-day owner email (A2-9). The plans go live.
- **Stage 3 (Self-serve):** full `/signup` self-serve, super-admin maturity, the polish set (A3) finished, Resources/SEO.
- **Stage 4 (Scale):** add-ons as gated features, white-label tier for groups/HMOs (your branding is already config-driven — the highest-value thing latent in the current build).

**The throughline, one more time:** "exquisite" right now is the daily friction gone and the operational layer smooth — not the feature count higher. Make the thing one clinic can't put down, make the public pages turn a stranger into a trial, make the next clinic painless to provision. Then widen the surface.
