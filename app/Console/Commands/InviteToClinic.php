<?php

namespace App\Console\Commands;

use App\Enums\StaffRole;
use App\Models\Clinic;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Notifications\StaffInvited;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Issue an invitation to a clinic from the command line.
 *
 * ── Why this exists ────────────────────────────────────────────────────────
 *
 * Removing /register closed a real hole, but it also removed the only way an
 * account could come into existence at a brand-new clinic. Invitations are sent
 * by an admin from /staff — and a freshly provisioned clinic has no admin to
 * send one. Without this command, deleting self-registration would leave clinic
 * #2 unable to onboard anybody at all, which is not a closed security gate so
 * much as a locked front door.
 *
 * So this is the bootstrap: an operator with server access — who by definition
 * already controls the database — issues the FIRST admin's invitation. Everyone
 * after that is invited from inside the application by that admin.
 *
 * A4's `tenant:create` should call this at the end of provisioning. Until it
 * exists, this is run by hand.
 *
 * The link is always printed as well as emailed. A new clinic is exactly where
 * mail is most likely to be unconfigured, and an operator who cannot see the
 * link has no way to recover it — nothing stores the raw token.
 */
class InviteToClinic extends Command
{
    protected $signature = 'clinic:invite
                            {subdomain : The clinic\'s subdomain}
                            {email : Who to invite}
                            {--role=admin : admin, doctor, nurse or receptionist}
                            {--name= : Their name, to pre-fill the form}
                            {--no-mail : Print the link without sending an email}';

    protected $description = "Issue a staff invitation for a clinic (bootstraps a new clinic's first admin)";

    public function handle(): int
    {
        $clinic = Clinic::where('subdomain', $this->argument('subdomain'))->first();

        if (! $clinic) {
            $this->error("No clinic with subdomain [{$this->argument('subdomain')}].");
            $this->line('Known clinics: '.(Clinic::pluck('subdomain')->implode(', ') ?: '(none)'));

            return self::FAILURE;
        }

        $role = StaffRole::tryFrom((string) $this->option('role'));

        if (! $role) {
            $this->error('Role must be one of: '.implode(', ', array_column(StaffRole::cases(), 'value')));

            return self::FAILURE;
        }

        $email = (string) $this->argument('email');

        // Everything below runs against the CLINIC's database, which is where
        // both staff and invitations live.
        return $clinic->run(function () use ($clinic, $email, $role) {
            if (Staff::withTrashed()->where('email', $email)->exists()) {
                $this->error("{$email} already has an account at {$clinic->name}.");

                return self::FAILURE;
            }

            [$invitation, $token] = StaffInvitation::issue(
                email: $email,
                role: $role,
                name: $this->option('name') ?: null,
                invitedBy: null,   // issued by an operator, not by a staff member
            );

            /*
             * Built from the clinic's own address rather than route(), because
             * there is no request here — the {clinic} URL default that tenant
             * route generation relies on is set by the identification
             * middleware, which never ran.
             */
            $url = $clinic->url().'/invite/'.$token;

            $this->newLine();
            $this->info("Invitation issued for {$email} as {$role->label()} at {$clinic->name}.");
            $this->line('Expires in '.StaffInvitation::EXPIRES_AFTER_DAYS.' days. It can be used once.');
            $this->newLine();
            $this->line($url);
            $this->newLine();

            if ($this->option('no-mail')) {
                $this->comment('No email sent (--no-mail). Give the link to them over a channel you trust.');

                return self::SUCCESS;
            }

            try {
                Notification::route('mail', $email)->notify(new StaffInvited(
                    clinicName: $clinic->name,
                    roleLabel: $role->label(),
                    acceptUrl: $url,
                    invitedByName: 'The AfriChart team',
                    expiresInDays: StaffInvitation::EXPIRES_AFTER_DAYS,
                ));

                $this->info('Emailed to '.$email.'.');
            } catch (\Throwable $e) {
                // Not a failure: the invitation exists and the link is above.
                $this->warn('Could not send the email: '.$e->getMessage());
                $this->warn('The invitation is still valid — use the link above.');
            }

            return self::SUCCESS;
        });
    }
}
