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
                    ['label' => 'WhatsApp support', 'included' => true],
                ],
            ],
            [
                'name' => 'Group',
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
                    ['label' => 'Priority support & onboarding', 'included' => true],
                ],
            ],
        ];
    }
}
