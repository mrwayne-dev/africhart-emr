<?php

namespace App\Http\Controllers\Settings;

use App\Enums\StaffRole;
use App\Http\Controllers\BaseController;
use App\Models\Staff;
use App\Models\StaffInvitation;
use Illuminate\View\View;

/**
 * Team & Seats — the clinic's people, folded into the settings hub.
 *
 * This is the /staff page moved rather than rebuilt: same query, same actions
 * (invite, revoke, deactivate, reactivate), now rendered inside the settings
 * shell so managing the team sits beside the clinic's other configuration
 * instead of in a separate corner of the sidebar. /staff redirects here so
 * existing links and bookmarks keep working.
 *
 * "Seats" is in the section's NAME but not enforced anywhere, deliberately. A
 * seat limit is a plan entitlement, and plan gating is B2 — which needs B1's
 * billing before it can know what a clinic is entitled to. Counting staff
 * against a limit that no subscription defines would be inventing a commercial
 * term. This surface manages the team; it does not police it.
 */
class TeamController extends BaseController
{
    public function index(): View
    {
        $staff = Staff::withTrashed()->orderBy('name')->get();

        // Only invitations still worth acting on. Accepted ones are represented
        // by the staff member they became, and expired or revoked rows are
        // history — listing them would turn this into a log.
        $invitations = StaffInvitation::query()
            ->pending()
            ->with('inviter')
            ->orderBy('created_at')
            ->get();

        return view('settings.team', [
            'staff' => $staff,
            'invitations' => $invitations,
            'roles' => StaffRole::cases(),
            'seatsUsed' => $staff->whereNull('deleted_at')->count(),
        ]);
    }
}
