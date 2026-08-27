<?php

namespace App\Http\Controllers;

use App\Enums\StaffRole;
use App\Http\Requests\SendInvitationRequest;
use App\Models\StaffInvitation;
use App\Notifications\StaffInvited;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Admin-side invitation management, on the role:admin-gated /staff page.
 */
class StaffInvitationController extends BaseController
{
    public function store(SendInvitationRequest $request): RedirectResponse
    {
        $data = $request->validated();

        [$invitation, $token] = StaffInvitation::issue(
            email: $data['email'],
            role: StaffRole::from($data['role']),
            name: $data['name'] ?? null,
            invitedBy: auth()->id(),
        );

        /*
         * Built HERE, in the request, not inside the queued notification.
         *
         * Tenant routes carry a {clinic} domain parameter that the
         * identification middleware registers as a URL default per request. A
         * queue worker never ran that middleware, so generating the link there
         * would either fail outright or — if some other tenant's default were
         * set — produce a working link to the WRONG clinic.
         */
        $acceptUrl = route('invite.show', ['token' => $token]);

        /*
         * on-demand: the invitee has no Staff record yet, so there is no
         * notifiable model to route the mail through.
         *
         * Mail failure must not lose the invitation. The row is already
         * committed above; if the send throws, the admin is told to try again
         * and the pending invitation is visible on the page with a revoke
         * control, rather than the whole action rolling back and leaving them
         * unsure whether it went out.
         */
        try {
            Notification::route('mail', $invitation->email)->notify(new StaffInvited(
                clinicName: tenant('name'),
                roleLabel: $invitation->role->label(),
                acceptUrl: $acceptUrl,
                invitedByName: auth()->user()->name,
                expiresInDays: StaffInvitation::EXPIRES_AFTER_DAYS,
            ));
        } catch (\Throwable $e) {
            Log::error('Staff invitation email failed', [
                'invitation_id' => $invitation->id,
                'message' => $e->getMessage(),
            ]);

            return back()->with(
                'error',
                "The invitation for {$invitation->email} was created but the email could not be sent. "
                .'Revoke it below and try again.'
            );
        }

        return back()->with('success', "Invitation sent to {$invitation->email}.");
    }

    public function destroy(StaffInvitation $invitation): RedirectResponse
    {
        if (! $invitation->isPending()) {
            return back()->with('error', 'That invitation is no longer open.');
        }

        $invitation->revoke();

        return back()->with('success', "The invitation for {$invitation->email} has been revoked.");
    }
}
