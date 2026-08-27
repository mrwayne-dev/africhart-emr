<?php

namespace App\Http\Controllers;

use App\Enums\StaffRole;
use App\Models\Staff;
use App\Models\StaffInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Staff oversight for admins: the people who have access to this clinic, the
 * invitations that have not been taken up yet, and the controls to deactivate,
 * reactivate or revoke.
 *
 * This page is now the ONLY way an account comes into existence at a clinic
 * (after the first admin, who is created with the clinic itself). Self-service
 * registration was removed with the global invite codes it depended on.
 */
class StaffController extends BaseController
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

        return view('staff.index', [
            'staff' => $staff,
            'invitations' => $invitations,
            'roles' => StaffRole::cases(),
        ]);
    }

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
