<?php

namespace App\Http\Controllers\Settings;

use App\Enums\StaffRole;
use App\Http\Controllers\BaseController;
use App\Http\Requests\SetupProfileRequest;
use App\Models\Medication;
use App\Models\Setting;
use App\Support\ClinicIdentity;
use App\Support\ClinicSetup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The first-run wizard: profile -> drug list -> team.
 *
 * Three steps, three requests, each saving on its own. A single form posting at
 * the end would lose everything to one validation error on the last field, and
 * an owner who gets called away mid-setup should come back to what they already
 * entered rather than an empty form.
 *
 * See App\Support\ClinicSetup for what provisioning has already done and why
 * each of these steps asks only for what it left.
 */
class SetupController extends BaseController
{
    /** Step 1 — the profile fields provisioning did not capture. */
    public function profile(): View
    {
        return view('settings.setup.profile', [
            'step' => 1,
            'progress' => ClinicSetup::progress(),
            'clinicName' => ClinicIdentity::name(),
        ]);
    }

    public function storeProfile(SetupProfileRequest $request): RedirectResponse
    {
        $data = $request->validated();

        foreach ([
            Setting::CLINIC_ADDRESS => $data['address'] ?? null,
            Setting::CLINIC_PHONE => $data['phone'] ?? null,
            Setting::CLINIC_EMAIL => $data['email'] ?? null,
            Setting::CONSULTATION_FEE => $data['consultation_fee'] ?? null,
            Setting::CLINIC_TIMEZONE => $data['timezone'] ?? null,
        ] as $key => $value) {
            if ($value !== null && $value !== '') {
                Setting::put($key, $value);
            }
        }

        ClinicIdentity::forget();

        return redirect()->route('setup.catalogue');
    }

    /**
     * Step 2 — REVIEW the drug list, not build one.
     *
     * tenant:create already seeded ten medications with default prices, so the
     * job here is confirming the prices are this clinic's, which is the part
     * that is actually wrong by default.
     */
    public function catalogue(): View
    {
        return view('settings.setup.catalogue', [
            'step' => 2,
            'progress' => ClinicSetup::progress(),
            'medications' => Medication::orderBy('name')->get(),
        ]);
    }

    public function storeCatalogue(Request $request): RedirectResponse
    {
        $prices = $request->input('prices', []);

        foreach ($prices as $id => $price) {
            if ($price === null || $price === '' || ! is_numeric($price)) {
                continue;
            }

            Medication::where('id', $id)->update(['default_price' => (float) $price]);
        }

        return redirect()->route('setup.team');
    }

    /** Step 3 — the rest of the team. The first admin is already in. */
    public function team(): View
    {
        return view('settings.setup.team', [
            'step' => 3,
            'progress' => ClinicSetup::progress(),
            'roles' => StaffRole::cases(),
        ]);
    }

    /**
     * Finish — or skip.
     *
     * Both land here on purpose. An owner who wants to get on with seeing
     * patients must be able to leave, and a wizard with no exit is a wizard
     * people learn to resent. The settings hub holds everything this asked for,
     * so nothing is lost by leaving early.
     */
    public function complete(): RedirectResponse
    {
        ClinicSetup::markComplete();

        return redirect()->route('dashboard')
            ->with('success', 'Your clinic is set up. You can change any of this in Settings.');
    }
}
