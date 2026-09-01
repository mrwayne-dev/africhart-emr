<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use Illuminate\Http\RedirectResponse;

/**
 * Staff oversight for admins: the people who have access to this clinic, the
 * invitations that have not been taken up yet, and the controls to deactivate,
 * reactivate or revoke.
 *
 * Listing and inviting moved to Settings -> Team & Seats
 * (App\Http\Controllers\Settings\TeamController) when B4 folded the team into
 * the settings hub. What remains here are the two ACTIONS, which are unchanged
 * and still reached from that screen.
 */
class StaffController extends BaseController
{
    public function deactivate(Staff $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->delete();

        return back()->with('success', "{$user->name} has been deactivated and can no longer sign in.");
    }

    public function reactivate(Staff $user): RedirectResponse
    {
        $user->restore();

        return back()->with('success', "{$user->name} has been reactivated.");
    }
}
