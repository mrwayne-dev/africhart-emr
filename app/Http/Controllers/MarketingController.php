<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\View\View;

/**
 * The public marketing site on the root domain.
 *
 * Deliberately has no middleware: these pages render for guests and signed-in
 * staff alike. Nothing here touches tenancy, billing or clinical data.
 */
class MarketingController extends BaseController
{
    public function home(): View
    {
        return view('marketing.home', [
            'tiers' => $this->tiers(),
        ]);
    }

    public function features(): View
    {
        return view('marketing.features');
    }

    public function pricing(): View
    {
        return view('marketing.pricing', [
            'tiers' => $this->tiers(),
            'comparison' => $this->comparison(),
        ]);
    }

    public function about(): View
    {
        return view('marketing.about');
    }

    public function privacy(): View
    {
        return view('marketing.legal.privacy');
    }

    public function terms(): View
    {
        return view('marketing.legal.terms');
    }

    public function dataProcessing(): View
    {
        return view('marketing.legal.data-processing');
    }

    /**
     * Comparison matrix for the pricing table.
     *
     * Kept separate from tiers(): those are three independent sales pitches with
     * their own wording, whereas a table needs one consistent row label compared
     * across all three columns. Merging them would force each tier to carry
     * every other tier's vocabulary.
     *
     * `true` renders a tick, `false` a dash, a string renders verbatim.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function comparison(): array
    {
        return [
            'Limits' => [
                ['label' => 'Sites', 'starter' => '1', 'clinic' => '1', 'group' => '2 or more'],
                ['label' => 'Staff accounts', 'starter' => 'Up to 3', 'clinic' => 'Unlimited', 'group' => 'Unlimited'],
                ['label' => 'Patients', 'starter' => 'Unlimited', 'clinic' => 'Unlimited', 'group' => 'Unlimited'],
            ],
            'Daily clinic work' => [
                ['label' => 'Patient records & timeline', 'starter' => true, 'clinic' => true, 'group' => true],
                ['label' => 'Live queue', 'starter' => true, 'clinic' => true, 'group' => true],
                ['label' => 'Vitals at check-in', 'starter' => true, 'clinic' => true, 'group' => true],
                ['label' => 'Consultations & prescriptions', 'starter' => true, 'clinic' => true, 'group' => true],
                ['label' => 'Drug catalogue with your prices', 'starter' => true, 'clinic' => true, 'group' => true],
                ['label' => 'Invoicing & payment recording', 'starter' => true, 'clinic' => true, 'group' => true],
            ],
            'Oversight' => [
                ['label' => 'Audit log', 'starter' => false, 'clinic' => true, 'group' => true],
                ['label' => 'Owner dashboard', 'starter' => false, 'clinic' => true, 'group' => true],
                ['label' => 'PDF receipts', 'starter' => false, 'clinic' => true, 'group' => true],
                ['label' => 'CSV export', 'starter' => false, 'clinic' => true, 'group' => true],
                ['label' => 'Consolidated multi-site dashboard', 'starter' => false, 'clinic' => false, 'group' => true],
            ],
            'Platform' => [
                ['label' => 'Own database per clinic', 'starter' => true, 'clinic' => true, 'group' => true],
                ['label' => 'Daily encrypted backups', 'starter' => true, 'clinic' => true, 'group' => true],
                ['label' => 'REST API access', 'starter' => false, 'clinic' => false, 'group' => true],
            ],
            'Support' => [
                ['label' => 'Channel', 'starter' => 'Email', 'clinic' => 'Priority email', 'group' => 'Priority email'],
                ['label' => 'Onboarding & setup', 'starter' => true, 'clinic' => true, 'group' => true],
                ['label' => 'Dedicated onboarding session', 'starter' => false, 'clinic' => false, 'group' => true],
            ],
        ];
    }

    /**
     * Presentation-only plan data, kept here rather than in config or the
     * database because nothing enforces it yet. Phase 2 item B2 introduces
     * real plans + entitlements in the central DB; this is replaced then.
     *
     * @return array<int, array<string, mixed>>
     */
    /**
     * The pricing tiers, read from the `plans` table.
     *
     * This used to be a hardcoded array, which meant the figures a clinic was
     * quoted lived somewhere different from the figures billing would charge.
     * The table is the single source of truth now — change a price in
     * PlanSeeder, re-seed, and every surface follows.
     *
     * The array SHAPE is preserved so the pricing views did not have to change
     * with the source: they still receive name/slug/price/setup/blurb/cta/
     * featured/features, only now each value came out of the database.
     *
     * @return array<int, array<string, mixed>>
     */
    private function tiers(): array
    {
        return Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Plan $plan) => [
                'name' => $plan->name,
                'slug' => $plan->slug,
                'price' => $plan->formattedMonthlyPrice(),
                'priceSuffix' => $plan->priceSuffix(),
                // Raw naira too, so a view can compute a worked example (e.g.
                // "two sites on Group") instead of a human retyping the product.
                'monthlyNaira' => $plan->monthlyPriceNaira(),
                'perSite' => $plan->isPerSite(),
                'setup' => $plan->formattedSetupFee(),
                'blurb' => $plan->blurb,
                'featured' => $plan->is_featured,
                'cta' => $plan->cta_label,
                'features' => $plan->highlights ?? [],
            ])
            ->all();
    }
}
