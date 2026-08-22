<?php

namespace App\Http\Controllers;

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
    private function tiers(): array
    {
        return [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'price' => '25,000',
                'setup' => '50,000',
                'blurb' => 'For a single clinic finding its feet.',
                'featured' => false,
                'cta' => 'Start free trial',
                'features' => [
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
                'name' => 'Clinic',
                'slug' => 'clinic',
                'price' => '50,000',
                'setup' => '75,000',
                'blurb' => 'For an owner who wants to see everything.',
                'featured' => true,
                'cta' => 'Start free trial',
                'features' => [
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
                'name' => 'Group',
                'slug' => 'group',
                'price' => '40,000',
                'setup' => '100,000',
                'blurb' => 'Per site, for two or more locations.',
                'featured' => false,
                'cta' => 'Talk to us',
                'features' => [
                    ['label' => '2+ sites', 'included' => true],
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
