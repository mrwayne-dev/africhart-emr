<?php

namespace Tests\Tenancy;

use App\Enums\StaffRole;
use App\Models\Clinic;
use App\Models\Staff;
use App\Models\StaffInvitation;
use App\Notifications\StaffInvited;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

/**
 * §6 — INVITATION ISOLATION, and the rest of the invite system's guarantees.
 *
 * These replace four global invite codes: one value per role for the entire
 * server, so the same admin code created an admin at EVERY clinic. That is the
 * leak being closed, and the headline test below is written as an attempt to
 * perform it.
 *
 * The refusal is expected to be STRUCTURAL. `staff_invitations` lives in the
 * tenant database, so clinic A's row is not present in clinic B's — there is no
 * "does this belong here?" comparison anywhere in the controller. A test that
 * only asserted "B says no" could pass against a broken token, a typo'd route,
 * or a system where nothing works at all, so every refusal here is paired with
 * a control proving the same token DOES work on A.
 */
class InviteIsolationTest extends TenancyTestCase
{
    // ── The headline: a token from A, presented to B ───────────────────────

    public function test_an_invitation_from_one_clinic_cannot_be_accepted_at_another(): void
    {
        $a = $this->provisionClinic('alpha');
        $b = $this->provisionClinic('bravo');

        [, $token] = $this->issueInvitation($a, 'newdoctor@alpha.test', StaffRole::Doctor);

        $staffInBBefore = $this->inTenant($b, fn () => Staff::withTrashed()->count());

        // The attempt: A's token, B's subdomain.
        $this->refreshApplication();
        $onB = $this->post($this->url($b, "/invite/{$token}"), [
            'name' => 'Intruder',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $onB->assertNotFound();

        $this->assertSame(
            $staffInBBefore,
            $this->inTenant($b, fn () => Staff::withTrashed()->count()),
            'Accepting another clinic\'s invitation must not create a staff row in this one.',
        );

        $this->assertNull(
            $this->inTenant($b, fn () => Staff::withTrashed()->where('email', 'newdoctor@alpha.test')->first()),
            'The invited address must not exist as staff at the clinic that did not invite them.',
        );

        /*
         * THE CONTROL, and the reason the assertion above means anything.
         *
         * If the token were simply broken, or the route misspelt, B would refuse
         * it and this test would "pass" while proving nothing. A must accept the
         * very same token.
         */
        $this->refreshApplication();
        $onA = $this->post($this->url($a, "/invite/{$token}"), [
            'name' => 'Real Doctor',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $onA->assertRedirect($this->url($a, '/dashboard'));

        $this->assertNotNull(
            $this->inTenant($a, fn () => Staff::where('email', 'newdoctor@alpha.test')->first()),
            'The same token must work at the clinic that issued it — otherwise the refusal above was not isolation.',
        );
    }

    public function test_the_invitation_row_exists_only_in_the_clinic_that_issued_it(): void
    {
        $a = $this->provisionClinic('alpha');
        $b = $this->provisionClinic('bravo');

        [$invitation] = $this->issueInvitation($a, 'newdoctor@alpha.test', StaffRole::Doctor);

        $this->assertSame(
            1,
            $this->inTenant($a, fn () => StaffInvitation::count()),
            'The issuing clinic must hold the invitation.',
        );

        $this->assertSame(
            0,
            $this->inTenant($b, fn () => StaffInvitation::count()),
            'No trace of it may appear in another clinic — this absence IS the isolation.',
        );

        $this->assertNull(
            $this->inTenant($b, fn () => StaffInvitation::find($invitation->id)),
            'Even by primary key, the other clinic must not see it.',
        );
    }

    // ── Single use, expiry, revocation ─────────────────────────────────────

    public function test_an_invitation_cannot_be_used_twice(): void
    {
        $clinic = $this->provisionClinic('alpha');
        [, $token] = $this->issueInvitation($clinic, 'once@alpha.test', StaffRole::Nurse);

        $this->refreshApplication();
        $this->post($this->url($clinic, "/invite/{$token}"), $this->acceptancePayload('First Use'))
            ->assertRedirect($this->url($clinic, '/dashboard'));

        $this->refreshApplication();
        $this->post($this->url($clinic, "/invite/{$token}"), $this->acceptancePayload('Second Use'))
            ->assertNotFound();

        $this->assertSame(
            1,
            $this->inTenant($clinic, fn () => Staff::where('email', 'once@alpha.test')->count()),
            'A replayed invitation must not produce a second account.',
        );
    }

    public function test_an_expired_invitation_is_refused(): void
    {
        $clinic = $this->provisionClinic('alpha');
        [$invitation, $token] = $this->issueInvitation($clinic, 'late@alpha.test', StaffRole::Nurse);

        // One second past the boundary, not a month — a test that only proves
        // "very old fails" would miss an off-by-one in the comparison.
        $this->inTenant($clinic, fn () => $invitation->forceFill([
            'expires_at' => now()->subSecond(),
        ])->save());

        $this->refreshApplication();
        $this->post($this->url($clinic, "/invite/{$token}"), $this->acceptancePayload('Too Late'))
            ->assertNotFound();

        $this->assertSame(0, $this->inTenant($clinic, fn () => Staff::where('email', 'late@alpha.test')->count()));
    }

    public function test_a_revoked_invitation_is_refused(): void
    {
        $clinic = $this->provisionClinic('alpha');
        [$invitation, $token] = $this->issueInvitation($clinic, 'revoked@alpha.test', StaffRole::Nurse);

        $this->inTenant($clinic, fn () => $invitation->revoke());

        $this->refreshApplication();
        $this->post($this->url($clinic, "/invite/{$token}"), $this->acceptancePayload('Revoked'))
            ->assertNotFound();

        $this->assertSame(0, $this->inTenant($clinic, fn () => Staff::where('email', 'revoked@alpha.test')->count()));
    }

    // ── Role comes from the record, not the request ────────────────────────

    public function test_a_valid_invitation_creates_staff_with_the_invited_role(): void
    {
        $clinic = $this->provisionClinic('alpha');
        [, $token] = $this->issueInvitation($clinic, 'nurse@alpha.test', StaffRole::Nurse);

        $this->refreshApplication();
        $this->post($this->url($clinic, "/invite/{$token}"), $this->acceptancePayload('New Nurse'))
            ->assertRedirect($this->url($clinic, '/dashboard'));

        /*
         * Every attribute is read INSIDE the closure, while tenant context is
         * still active. Eloquent casts lazily: touching $staff->email_verified_at
         * after tenancy()->end() sends the datetime cast looking for the
         * `tenant` connection to ask for its date format, and that connection
         * has been purged — "Database connection [tenant] not configured",
         * thrown by an assertion that has nothing to do with databases.
         */
        $staff = $this->inTenant($clinic, function () {
            $staff = Staff::where('email', 'nurse@alpha.test')->first();

            return $staff === null ? null : [
                'role' => $staff->role,
                'name' => $staff->name,
                'verified' => $staff->email_verified_at !== null,
            ];
        });

        $this->assertNotNull($staff);
        $this->assertSame(StaffRole::Nurse, $staff['role']);
        $this->assertSame('New Nurse', $staff['name']);

        // Delivery to the address proved it; the 6-digit step is skipped.
        $this->assertTrue($staff['verified'], 'An invited staff member arrives already verified.');
    }

    public function test_posting_a_different_role_does_not_change_the_role_granted(): void
    {
        $clinic = $this->provisionClinic('alpha');
        [, $token] = $this->issueInvitation($clinic, 'climber@alpha.test', StaffRole::Receptionist);

        $this->refreshApplication();
        $this->post($this->url($clinic, "/invite/{$token}"), [
            'name' => 'Would Be Admin',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            // The old flow read exactly this from the request.
            'role' => 'admin',
            'email' => 'someone.else@alpha.test',
        ])->assertRedirect($this->url($clinic, '/dashboard'));

        $staff = $this->inTenant($clinic, fn () => Staff::where('email', 'climber@alpha.test')->first());

        $this->assertNotNull($staff, 'The invited address must be the one enrolled.');
        $this->assertSame(
            StaffRole::Receptionist,
            $staff->role,
            'Role must come from the invitation. Reading it from the request is the privilege escalation this replaced.',
        );

        $this->assertNull(
            $this->inTenant($clinic, fn () => Staff::where('email', 'someone.else@alpha.test')->first()),
            'The posted email must be ignored — the invitation names the address.',
        );
    }

    // ── Non-enumeration ────────────────────────────────────────────────────

    public function test_every_failure_mode_returns_an_identical_response(): void
    {
        $a = $this->provisionClinic('alpha');
        $b = $this->provisionClinic('bravo');

        // 1. A token that was never issued.
        $unknown = str_repeat('z', 64);

        // 2. Expired.
        [$expired, $expiredToken] = $this->issueInvitation($a, 'expired@alpha.test', StaffRole::Nurse);
        $this->inTenant($a, fn () => $expired->forceFill(['expires_at' => now()->subDay()])->save());

        // 3. Revoked.
        [$revoked, $revokedToken] = $this->issueInvitation($a, 'revoked@alpha.test', StaffRole::Nurse);
        $this->inTenant($a, fn () => $revoked->revoke());

        // 4. Already accepted.
        [, $usedToken] = $this->issueInvitation($a, 'used@alpha.test', StaffRole::Nurse);
        $this->refreshApplication();
        $this->post($this->url($a, "/invite/{$usedToken}"), $this->acceptancePayload('Used Already'));

        // 5. Belongs to another clinic.
        [, $foreignToken] = $this->issueInvitation($b, 'foreign@bravo.test', StaffRole::Nurse);

        $bodies = [];
        $statuses = [];

        foreach ([
            'unknown' => $unknown,
            'expired' => $expiredToken,
            'revoked' => $revokedToken,
            'used' => $usedToken,
            'wrong clinic' => $foreignToken,
        ] as $label => $token) {
            $this->refreshApplication();

            /*
             * Each probe starts from the same rate-limit state.
             *
             * Without this the acceptance route's throttle counts all five and
             * the later ones come back 429 while the first is 404 — a
             * difference caused by request ORDER, not by which failure mode
             * they hit. That would fail this test for the wrong reason and,
             * worse, could hide a real difference behind an explicable one.
             */
            Cache::flush();

            $response = $this->get($this->url($a, "/invite/{$token}"));

            $statuses[$label] = $response->getStatusCode();

            /*
             * The CSRF token is masked out before comparing. It is re-randomised
             * on every response and is identical in shape whatever the outcome,
             * so it carries nothing about which failure mode was hit — but left
             * in, it would make every body differ and this assertion could never
             * fail for the reason it exists.
             *
             * Nothing else is normalised. The token used to be reflected back
             * into <link rel=canonical> and og:url by the shared head partial,
             * which DID make the bodies differ meaningfully; that is fixed at
             * source rather than papered over here, so an identical page is now
             * genuinely identical.
             */
            $bodies[$label] = preg_replace(
                '/<meta name="csrf-token" content="[^"]*">/',
                '<meta name="csrf-token" content="MASKED">',
                $response->getContent(),
            );
        }

        $this->assertSame(
            [404, 404, 404, 404, 404],
            array_values($statuses),
            'Every failure mode must share one status: '.json_encode($statuses),
        );

        $this->assertCount(
            1,
            array_unique($bodies),
            'All five failure modes must render byte-identical pages. Any difference lets someone '
            .'probe which tokens exist and which clinic they belong to.',
        );
    }

    // ── Who may issue one ──────────────────────────────────────────────────

    public function test_an_admin_can_issue_an_invitation_and_it_is_emailed(): void
    {
        $clinic = $this->provisionClinic('alpha');
        $this->makeStaff($clinic, 'admin@alpha.test', StaffRole::Admin);

        $this->refreshApplication();

        // AFTER refreshApplication(), not before: rebuilding the container
        // replaces the fake with the real ChannelManager, and the assertion
        // then fails with "assertSentOnDemand does not exist" rather than
        // anything about notifications.
        Notification::fake();

        $this->login($clinic, 'admin@alpha.test');

        $this->post($this->url($clinic, '/staff/invitations'), [
            'email' => 'newnurse@alpha.test',
            'name' => 'New Nurse',
            'role' => 'nurse',
        ])->assertSessionHasNoErrors();

        $invitation = $this->inTenant($clinic, fn () => StaffInvitation::where('email', 'newnurse@alpha.test')->first());

        $this->assertNotNull($invitation, 'The invitation must be recorded in the clinic.');
        $this->assertSame(StaffRole::Nurse, $invitation->role);

        Notification::assertSentOnDemand(StaffInvited::class);
    }

    public function test_a_non_admin_cannot_issue_an_invitation(): void
    {
        $clinic = $this->provisionClinic('alpha');
        $this->makeStaff($clinic, 'reception@alpha.test', StaffRole::Receptionist);

        $this->refreshApplication();
        $this->login($clinic, 'reception@alpha.test');

        $this->post($this->url($clinic, '/staff/invitations'), [
            'email' => 'selfmade@alpha.test',
            'role' => 'admin',
        ])->assertForbidden();

        $this->assertSame(
            0,
            $this->inTenant($clinic, fn () => StaffInvitation::count()),
            'A receptionist inviting themselves an admin is the escalation this must refuse.',
        );
    }

    public function test_revoking_an_invitation_stops_the_link_working(): void
    {
        $clinic = $this->provisionClinic('alpha');
        $this->makeStaff($clinic, 'admin@alpha.test', StaffRole::Admin);
        [$invitation, $token] = $this->issueInvitation($clinic, 'revokeme@alpha.test', StaffRole::Nurse);

        $this->refreshApplication();
        $this->login($clinic, 'admin@alpha.test');

        $this->delete($this->url($clinic, "/staff/invitations/{$invitation->id}"))
            ->assertSessionHasNoErrors();

        $this->refreshApplication();
        $this->get($this->url($clinic, "/invite/{$token}"))->assertNotFound();

        $this->assertSame(0, $this->inTenant($clinic, fn () => Staff::where('email', 'revokeme@alpha.test')->count()));
    }

    // ── The old mechanism is gone, not disabled ────────────────────────────

    public function test_the_self_registration_route_no_longer_exists(): void
    {
        $clinic = $this->provisionClinic('alpha');

        $this->refreshApplication();
        $this->get($this->url($clinic, '/register'))->assertNotFound();

        $this->refreshApplication();
        $this->post($this->url($clinic, '/register'), [
            'name' => 'Self Registered',
            'email' => 'self@alpha.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
            'invite_code' => 'ACH-ADMIN-7F3K9X',   // the code that used to work
        ])->assertNotFound();

        $this->assertSame(
            0,
            $this->inTenant($clinic, fn () => Staff::withTrashed()->count()),
            'Self-registration must not create staff by any route.',
        );
    }

    public function test_the_global_invite_codes_are_gone_from_configuration(): void
    {
        $this->assertNull(
            config('registration.codes'),
            'config(registration.codes) must not exist. Leaving the key in place means the old '
            .'global-code path can be reopened by setting an environment variable.',
        );
    }

    // ── helpers ────────────────────────────────────────────────────────────

    /**
     * Issue an invitation inside a clinic.
     *
     * @return array{0: StaffInvitation, 1: string}
     */
    private function issueInvitation(Clinic $clinic, string $email, StaffRole $role): array
    {
        return $this->inTenant($clinic, fn () => StaffInvitation::issue($email, $role, null, null));
    }

    /**
     * @return array<string, string>
     */
    private function acceptancePayload(string $name): array
    {
        return [
            'name' => $name,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
    }

    private function makeStaff(Clinic $clinic, string $email, StaffRole $role): void
    {
        $this->inTenant($clinic, function () use ($email, $role) {
            Staff::create([
                'name' => 'Test '.$role->value,
                'email' => $email,
                'password' => 'password123',
                'role' => $role,
            ])->forceFill(['email_verified_at' => now()])->save();
        });
    }

    private function login(Clinic $clinic, string $email): void
    {
        $this->post($this->url($clinic, '/login'), [
            'email' => $email,
            'password' => 'password123',
        ])->assertRedirect($this->url($clinic, '/dashboard'));
    }

    private function url(Clinic $clinic, string $path): string
    {
        return "http://{$clinic->subdomain}.".config('tenancy.root_domain').$path;
    }
}
