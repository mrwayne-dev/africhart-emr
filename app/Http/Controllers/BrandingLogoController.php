<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves the current clinic's logo.
 *
 * A fixed route with NO path parameter. The filename comes from the clinic's
 * own settings row, never from the URL, so there is no traversal surface to
 * validate — the usual defence against ../ is unnecessary because nothing from
 * the request reaches the filesystem.
 *
 * Isolation is by location: this reads the tenant's own public disk, which
 * stancl's filesystem bootstrapper has already pointed at
 * storage/tenant<uuid>/app/public. Two clinics asking for /branding/logo on
 * their own subdomains read two different files without any check being
 * written.
 *
 * Deliberately NOT stancl's TenantAssetsController: that is bound to
 * InitializeTenancyByDomain and calls $tenant->domains(), a relation Clinic
 * does not have. It 500s here — the A6 defect.
 */
class BrandingLogoController extends BaseController
{
    public function __invoke(): Response
    {
        $path = Setting::get(Setting::CLINIC_LOGO);

        abort_if(! $path, 404);

        $disk = Storage::disk('public');

        abort_if(! $disk->exists($path), 404);

        /*
         * A logo changes rarely and is fetched on every invoice view. Cached
         * privately rather than publicly: it is one clinic's mark, and a shared
         * proxy holding it under a URL every tenant shares would be the one way
         * this could leak across clinics.
         */
        return response()->file($disk->path($path), [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
