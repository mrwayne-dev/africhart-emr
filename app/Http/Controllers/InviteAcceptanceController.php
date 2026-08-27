<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcceptInvitationRequest;
use App\Models\Staff;
use App\Models\StaffInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Accepting an invitation, on the clinic's own subdomain.
 *
 * ── Why a token presented to the wrong clinic fails ────────────────────────
 *
 * It is not compared against anything. `staff_invitations` lives in the tenant
 * database, so by the time this controller runs, the connection already points
 * at whichever clinic the subdomain resolved to. Clinic A's row is not in
 * clinic B's database, so the lookup returns null in exactly the way an
 * invented token does.
 *
 * There is therefore no "does this invite belong to this clinic?" check in this
 * file — deliberately. A check is something a later refactor can drop, get
 * backwards, or skip on one of two paths. Storage location cannot be forgotten.
 *
 * ── Why every failure looks the same ───────────────────────────────────────
 *
 * Unknown, expired, already used, revoked and belonging-to-another-clinic all
 * render the SAME view with the SAME status. Telling them apart would let
 * someone with a list of tokens learn which exist and which clinic they belong
 * to — and "already used" in particular would confirm a real staff member's
 * email at a named clinic. Same reasoning as the single miss message on
 * find-your-clinic.
 */
class InviteAcceptanceController extends BaseController
{
    public function show(string $token): Response
    {
        $invitation = StaffInvitation::findPending($token);

        if (! $invitation) {
            return $this->invalid();
        }

        return response()->view('auth.invite', [
            'token' => $token,
            'invitation' => $invitation,
            'clinicName' => tenant('name'),
        ]);
    }

    public function accept(AcceptInvitationRequest $request, string $token): RedirectResponse|Response
    {
        $data = $request->validated();

        /*
         * The invitation is re-read INSIDE the transaction, under
         * lockForUpdate() — not carried over from the GET, and not read before
         * the transaction opens.
         *
         * Single use has to be enforced here or it is not enforced at all. A
         * check followed by a write leaves a window: two requests carrying the
         * same token can both see it as pending and both proceed. The unique
         * index on staff.email happens to stop the second account being
         * created, but that is a different table's constraint catching this
         * table's race, and it surfaces as a 500 rather than a refusal. The row
         * lock makes the second request wait, then find accepted_at already set.
         *
         * One transaction, because two rows must agree: an invitation marked
         * accepted with no staff member, or a staff member whose invitation
         * still reads as open (and so is reusable), are both worse than
         * failing outright.
         *
         * Note what is NOT taken from $data: role and email. Those come from
         * the invitation. Posting role=admin to this endpoint changes nothing,
         * because nothing here reads it.
         */
        $staff = DB::transaction(function () use ($token, $data) {
            $invitation = StaffInvitation::query()
                ->where('token_hash', StaffInvitation::hash($token))
                ->lockForUpdate()
                ->first();

            // Unknown, expired, revoked or already accepted — indistinguishable
            // to the caller, by design.
            if (! $invitation || ! $invitation->isPending()) {
                return null;
            }

            $staff = Staff::create([
                'name' => $data['name'],
                'email' => $invitation->email,
                'password' => $data['password'],
                'role' => $invitation->role,
            ]);

            /*
             * Verified on arrival. The invitation was delivered to this address
             * and the token came back from it, which is stronger evidence than
             * the 6-digit code the old flow emailed to an address it had just
             * accepted on trust. Asking them to prove the same thing twice
             * would be ceremony.
             */
            $staff->forceFill(['email_verified_at' => now()])->save();

            $invitation->markAccepted($staff);

            return $staff;
        });

        if (! $staff) {
            return $this->invalid();
        }

        Auth::login($staff);

        // A fresh session id for a newly authenticated identity.
        request()->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('success', 'Welcome to '.tenant('name').", {$staff->name}.");
    }

    /**
     * The single response for every way an invitation can fail.
     *
     * 404 because, as far as this clinic is concerned, there is no such
     * invitation — which is literally true for a token belonging to another
     * clinic.
     */
    protected function invalid(): Response
    {
        return response()->view('auth.invite-invalid', [], 404);
    }
}
