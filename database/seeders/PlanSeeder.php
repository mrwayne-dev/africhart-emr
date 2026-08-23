<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Subscription tiers — CENTRAL reference data.
 *
 * Not optional decoration: clinics.plan carries a foreign key to plans.slug, so
 * until this has run no clinic can be provisioned at all.
 *
 * ⚠️ These figures are currently duplicated. /pricing serves them from a
 * hardcoded array in MarketingController::tiers(), and they are repeated here
 * because the page predates this table. Two sources of truth for what a clinic
 * pays is exactly the kind of drift that ends in a customer being billed
 * something other than the advertised price.
 *
 * B2 must collapse this: the pricing page should read from `plans`, and this
 * seeder should become the only place the numbers live. Until then, changing
 * one means changing both.
 *
 * Also still open: SOW Appendix 1 is blank, so these are the platform spec's
 * proposal rather than confirmed commercial terms.
 *
 * Money is kobo. ₦25,000 → 2_500_000.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'starter',
                'name' => 'Starter',
                'monthly_price' => 2_500_000,
                'setup_fee' => 5_000_000,
                'max_doctors' => 2,
                'max_sites' => 1,
                'sort_order' => 1,
                'features' => [
                    'patients' => true,
                    'queue' => true,
                    'consultations' => true,
                    'prescriptions' => true,
                    'billing' => true,
                    'drug_catalogue' => true,
                    'audit_log' => false,      // gated: visibility only, never the trait
                    'owner_dashboard' => false,
                    'exports' => false,
                    'api' => false,
                    'priority_support' => false,
                    'onboarding_session' => false,
                ],
            ],
            [
                'slug' => 'clinic',
                'name' => 'Clinic',
                'monthly_price' => 5_000_000,
                'setup_fee' => 7_500_000,
                'max_doctors' => 8,
                'max_sites' => 1,
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
            ],
            [
                'slug' => 'group',
                'name' => 'Group',
                'monthly_price' => 4_000_000,   // per site
                'setup_fee' => 10_000_000,
                'max_doctors' => null,          // null = unmetered
                'max_sites' => null,
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
            ],
        ];

        // updateOrCreate so re-seeding a live central database refreshes the
        // tiers rather than colliding on the unique slug.
        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
