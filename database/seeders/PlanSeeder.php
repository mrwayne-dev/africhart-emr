<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Subscription tiers — the SINGLE SOURCE OF TRUTH for pricing.
 *
 * ── Client-confirmed 2026-08-25 ────────────────────────────────────────────
 *
 *   Starter   ₦45,000/month   + ₦75,000 one-time setup
 *   Clinic    ₦85,000/month   + ₦120,000 one-time setup
 *   Group     ₦65,000/month PER SITE + ₦150,000 one-time setup
 *
 * These supersede the platform-spec proposal (₦25k/₦50k/₦40k) that SOW
 * Appendix 1 left blank. They are commercial terms now, not a suggestion.
 *
 * ── Why the numbers live here and nowhere else ─────────────────────────────
 *
 * They previously existed in FOUR places: this seeder, a hardcoded array in
 * MarketingController::tiers(), and twice in signup.blade.php. /pricing now
 * renders from this table, so changing a price is one edit here plus a re-seed.
 *
 * The monthly subscription and the one-time setup fee stay two distinct values.
 * They are different commitments — one recurs, one does not — and collapsing
 * them into a single "cost" would misrepresent both.
 *
 * ⚠️ ANNUAL PRICING IS NOT CONFIRMED. There is deliberately no annual column
 * and no annual toggle in the UI. Do not derive one from the monthly figure:
 * an annual price usually carries a discount, and inventing that discount would
 * be inventing a commercial term.
 *
 * ⚠️ CLINICS ONLY. Hospitals are out of scope — no inpatient/ward tier exists,
 * and Group means multiple outpatient CLINICS, not a hospital with departments.
 *
 * Money is kobo, as integers. ₦45,000 → 4_500_000.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->plans() as $plan) {
            // updateOrCreate so re-seeding a live central database refreshes the
            // tiers in place rather than colliding on the unique slug — which is
            // exactly what a price change needs to do.
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function plans(): array
    {
        return [
            [
                'slug' => 'starter',
                'name' => 'Starter',
                'blurb' => 'For a single clinic finding its feet.',
                'cta_label' => 'Start free trial',
                'monthly_price' => 4_500_000,   // ₦45,000
                'setup_fee' => 7_500_000,       // ₦75,000
                'price_basis' => 'flat',
                'max_doctors' => 2,
                'max_sites' => 1,
                'is_featured' => false,
                'sort_order' => 1,
                'features' => [
                    'patients' => true,
                    'queue' => true,
                    'consultations' => true,
                    'prescriptions' => true,
                    'billing' => true,
                    'drug_catalogue' => true,
                    'audit_log' => false,       // gated: visibility only, never the trait
                    'owner_dashboard' => false,
                    'exports' => false,
                    'api' => false,
                    'priority_support' => false,
                    'onboarding_session' => false,
                ],
                'highlights' => [
                    ['label' => '1 site', 'included' => true],
                    ['label' => 'Up to 3 staff', 'included' => true],
                    ['label' => 'Patients, queue & vitals', 'included' => true],
                    ['label' => 'Consultations & prescriptions', 'included' => true],
                    ['label' => 'Invoicing & receipts', 'included' => true],
                    ['label' => 'Audit log & oversight dashboard', 'included' => false],
                    ['label' => 'PDF receipts & CSV export', 'included' => false],
                    ['label' => 'Email support', 'included' => true],
                ],
            ],
            [
                'slug' => 'clinic',
                'name' => 'Clinic',
                'blurb' => 'For an owner who wants to see everything.',
                'cta_label' => 'Start free trial',
                'monthly_price' => 8_500_000,   // ₦85,000
                'setup_fee' => 12_000_000,      // ₦120,000
                'price_basis' => 'flat',
                'max_doctors' => 8,
                'max_sites' => 1,
                'is_featured' => true,
                'sort_order' => 2,
                'features' => [
                    'patients' => true,
                    'queue' => true,
                    'consultations' => true,
                    'prescriptions' => true,
                    'billing' => true,
                    'drug_catalogue' => true,
                    'audit_log' => true,
                    'owner_dashboard' => true,
                    'exports' => true,
                    'api' => false,
                    'priority_support' => true,
                    'onboarding_session' => false,
                ],
                'highlights' => [
                    ['label' => '1 site', 'included' => true],
                    ['label' => 'Unlimited staff', 'included' => true],
                    ['label' => 'Patients, queue & vitals', 'included' => true],
                    ['label' => 'Consultations & prescriptions', 'included' => true],
                    ['label' => 'Invoicing & receipts', 'included' => true],
                    ['label' => 'Audit log & oversight dashboard', 'included' => true],
                    ['label' => 'PDF receipts & CSV export', 'included' => true],
                    ['label' => 'Priority email support', 'included' => true],
                ],
            ],
            [
                'slug' => 'group',
                'name' => 'Group',
                'blurb' => 'Per site, for two or more clinic locations.',
                'cta_label' => 'Talk to us',
                'monthly_price' => 6_500_000,   // ₦65,000 PER SITE
                'setup_fee' => 15_000_000,      // ₦150,000 one-time, not per site
                'price_basis' => 'per_site',
                'max_doctors' => null,          // null = unmetered
                'max_sites' => null,
                'is_featured' => false,
                'sort_order' => 3,
                'features' => [
                    'patients' => true,
                    'queue' => true,
                    'consultations' => true,
                    'prescriptions' => true,
                    'billing' => true,
                    'drug_catalogue' => true,
                    'audit_log' => true,
                    'owner_dashboard' => true,
                    'exports' => true,
                    'api' => true,
                    'priority_support' => true,
                    'onboarding_session' => true,
                ],
                'highlights' => [
                    ['label' => '2+ clinic sites', 'included' => true],
                    ['label' => 'Unlimited staff', 'included' => true],
                    ['label' => 'Everything in Clinic', 'included' => true],
                    ['label' => 'Consolidated owner dashboard', 'included' => true],
                    ['label' => 'REST API access', 'included' => true],
                    ['label' => 'Priority email & onboarding', 'included' => true],
                ],
            ],
        ];
    }
}
