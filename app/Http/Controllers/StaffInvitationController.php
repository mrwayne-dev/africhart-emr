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

            return back()->with($this->linkPayload($invitation, $acceptUrl, delivered: false));
        }

        return back()->with($this->linkPayload($invitation, $acceptUrl, delivered: true));
    }

    /**
     * Hand the admin the invitation link, once, whatever the email did.
     *
     * ── Why this exists ────────────────────────────────────────────────────
     *
     * The link was previously built here and passed ONLY to the notification.
     * Nothing stores it — the table keeps a SHA-256 hash, by design, so a
     * leaked database is not a set of working invites. The consequence was that
     * if mail failed, or was stubbed, the link was gone: unrecoverable by
     * anyone, including us, and the admin's only move was revoke-and-reissue in
     * the hope that the next send worked.
     *
     * That made "invite a staff member" a feature that silently half-worked
     * wherever mail was not configured. Flashing the link closes it: the email
     * becomes a convenience, and the admin always has a path that does not
     * depend on it.
     *
     * Flashed, not stored. It lives for exactly one redirect and is never
     * written anywhere durable, so the hash-only guarantee is untouched. The
     * person reading it is the admin who just created the invitation and is
     * already authorised to hold it.
     *
     * @return array<string, mixed>
     */
    protected function linkPayload(StaffInvitation $invitation, string $acceptUrl, bool $delivered): array
    {
        return [
            'invite_link' => $acceptUrl,
            'invite_email' => $invitation->email,
            'invite_delivered' => $delivered,
            $delivered ? 'success' : 'error' => $delivered
                ? "Invitation created for {$invitation->email}."
                : "The invitation for {$invitation->email} was created, but the email could not be sent. "
                  .'Send them the link below instead.',
        ];
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
