<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Minimal staff oversight for admins: list staff and deactivate (soft-delete) or
 * reactivate accounts. Full team/seat management (invites, reassignment) is the
 * Settings → Team & Seats surface (platform spec Part B §12).
 */
class StaffController extends BaseController
{
    public function index(): View
    {
        $staff = User::withTrashed()->orderBy('name')->get();

        return view('staff.index', compact('staff'));
    }

    public function deactivate(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->delete();

        return back()->with('success', "{$user->name} has been deactivated and can no longer sign in.");
    }

    public function reactivate(User $user): RedirectResponse
    {
        $user->restore();

        return back()->with('success', "{$user->name} has been reactivated.");
    }
}
