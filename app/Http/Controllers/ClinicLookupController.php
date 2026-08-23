<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * "Find your clinic" — the staff entry point on the CENTRAL domain (T2.2).
 *
 * Login lives on each clinic's own subdomain, so central /login no longer
 * exists. This is the replacement path for the person who knows they are staff
 * somewhere but does not know their address.
 *
 * Deliberately central-only: it reads the clinic registry, which is exactly
 * what no tenant is allowed to do.
 */
class ClinicLookupController extends BaseController
{
    public function show(): View
    {
        return view('marketing.find-clinic');
    }

    public function find(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'clinic' => ['required', 'string', 'min:2', 'max:255'],
        ], [
            'clinic.required' => 'Enter your clinic name.',
        ]);

        $clinic = $this->match($validated['clinic']);

        if (! $clinic) {
            /*
             * Deliberately does NOT say whether the name exists but is
             * suspended, or does not exist at all. One message for every
             * miss — a lookup that distinguishes them leaks the customer
             * list to anyone willing to guess, and the route is throttled
             * for the same reason.
             */
            return back()
                ->withInput()
                ->withErrors(['clinic' => "We couldn't find a clinic by that name. Check the spelling, or ask your clinic admin for your link."]);
        }

        return redirect()->away($clinic->url().'/login');
    }

    /**
     * Match on the subdomain first, then the clinic's name.
     *
     * People type "Grace Medical Centre", not "grace" — but staff who half-know
     * their address type the subdomain. Slugifying the input the same way the
     * sign-up preview does means both spellings land on the same clinic.
     */
    private function match(string $input): ?Clinic
    {
        $slug = Str::slug($input);

        return Clinic::query()
            ->where('subdomain', $slug)
            ->orWhere('name', 'like', trim($input).'%')
            ->first();
    }
}
