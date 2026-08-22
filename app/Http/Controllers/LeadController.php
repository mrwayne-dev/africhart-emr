<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeadRequest;
use App\Models\MarketingLead;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Lead capture for the public marketing site.
 *
 * Neither endpoint creates a user account. `/signup` is a CLINIC asking for
 * access, which is fulfilled by manual provisioning (architecture doc Stage 1);
 * `/register` remains the separate invite-code path for a staff member joining
 * a clinic that already exists.
 */
class LeadController extends BaseController
{
    public function showDemo(): View
    {
        return view('marketing.demo');
    }

    public function storeDemo(LeadRequest $request): RedirectResponse
    {
        return $this->store($request, 'demo', 'Thanks — we have your details and will be in touch within one working day.');
    }

    public function showSignup(Request $request): View
    {
        /*
         * Two things arrive by query string:
         *  - ?clinic= from Home's address finder, so the visitor does not
         *    retype what they just typed;
         *  - ?plan= from the chosen tier's CTA on /pricing. Validated against
         *    the real tier slugs here rather than trusted, since it is
         *    reflected back into the page and stored on the lead.
         */
        $plan = $request->query('plan');

        return view('marketing.signup', [
            'clinic' => $request->query('clinic'),
            'plan' => in_array($plan, ['starter', 'clinic', 'group'], true) ? $plan : null,
        ]);
    }

    public function showContact(): View
    {
        return view('marketing.contact');
    }

    public function storeContact(LeadRequest $request): RedirectResponse
    {
        return $this->store($request, 'contact', 'Thanks — your message is with us. We reply within one working day.');
    }

    public function storeSignup(LeadRequest $request): RedirectResponse
    {
        return $this->store($request, 'signup', 'Thanks — we will set your clinic up and send login details shortly.');
    }

    private function store(LeadRequest $request, string $type, string $message): RedirectResponse
    {
        MarketingLead::create([
            // `terms` is consent, not data: the row's own timestamp records it.
            ...$request->safe()->except(['website', 'terms']),
            'type' => $type,
        ]);

        // Flash into the app's single toast mechanism rather than inventing a
        // second success pattern for the marketing surface.
        return back()->with('success', $message);
    }
}
