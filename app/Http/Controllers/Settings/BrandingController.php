<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\BaseController;
use App\Http\Requests\BrandingRequest;
use App\Models\Setting;
use App\Support\ClinicIdentity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Branding — the clinic's logo.
 *
 * The clinic's NAME is not here. It is central `clinics.name`, edited in Clinic
 * Profile (decision 1): a name is identity, and a second editable copy would be
 * free to drift from the one the registry knows. Branding covers presentation
 * only, which today means the logo.
 */
class BrandingController extends BaseController
{
    public function edit(): View
    {
        return view('settings.branding', [
            'logoUrl' => ClinicIdentity::logoUrl(),
            'clinicName' => ClinicIdentity::name(),
        ]);
    }

    public function update(BrandingRequest $request): RedirectResponse
    {
        /*
         * The tenant's OWN public disk. Under stancl's filesystem bootstrapper
         * storage_path() is suffixed per tenant, so this writes to
         * storage/tenant<uuid>/app/public/branding/ — the clinic's own
         * directory, not a shared one with a tenant-prefixed filename.
         * Isolation by location again.
         */
        $previous = Setting::get(Setting::CLINIC_LOGO);

        $path = $request->file('logo')->store('branding', 'public');

        Setting::put(Setting::CLINIC_LOGO, $path);
        ClinicIdentity::forget();

        /*
         * Delete the old file AFTER the new one is recorded, never before: if
         * the store or the settings write fails, the clinic still has the logo
         * it had this morning rather than none at all.
         */
        if ($previous && $previous !== $path) {
            Storage::disk('public')->delete($previous);
        }

        return back()->with('success', 'Logo updated. It now appears on your invoices.');
    }

    public function destroy(): RedirectResponse
    {
        if ($path = Setting::get(Setting::CLINIC_LOGO)) {
            Storage::disk('public')->delete($path);
        }

        Setting::put(Setting::CLINIC_LOGO, null);
        ClinicIdentity::forget();

        return back()->with('success', 'Logo removed. Your clinic name is used instead.');
    }
}
