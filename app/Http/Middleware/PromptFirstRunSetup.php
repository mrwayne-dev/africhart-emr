<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\ClinicSetup;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends a new clinic's admin to the setup wizard, once.
 *
 * Only an admin, only a clinic that has never been set up, and only one that
 * has no patients yet — see ClinicSetup::shouldPrompt(). That last condition is
 * what stops every EXISTING clinic being dropped into a setup wizard the day
 * this ships, since none of them carries the completion marker.
 *
 * A redirect rather than a banner because the fee in particular is not
 * optional: an unconfigured clinic bills the platform default on every invoice
 * it raises, and that is a wrong number on a document a patient keeps.
 */
class PromptFirstRunSetup
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            return $next($request);
        }

        /*
         * Never bounce a request that is already part of setup, or the exits
         * from it. Without this the wizard's own POSTs redirect back into the
         * wizard and the "skip" button can never be reached — a loop that
         * traps the user in the thing they are trying to leave.
         */
        if ($request->routeIs('setup.*', 'logout', 'settings.*', 'staff.invitations.*')) {
            return $next($request);
        }

        if (ClinicSetup::shouldPrompt()) {
            return redirect()->route('setup.profile');
        }

        return $next($request);
    }
}
