<?php

namespace Tests\Tenancy;

use App\Enums\StaffRole;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Setting;
use App\Models\Staff;
use App\Support\ClinicSetup;

/**
 * B4 — the first-run setup wizard, and the conditions that summon it.
 *
 * The gating is the part worth pinning in the suite rather than only in a
 * browser check. It has three states that must be told apart, and getting any
 * of them wrong is the kind of mistake that only shows up in front of a real
 * clinic:
 *
 *   fresh      no marker, no patients   -> prompt
 *   completed  marker set               -> never again
 *   in use     patients exist           -> never, marker or not
 *
 * That last one is not defensive padding. Every clinic that existed before this
 * shipped carries no marker, so without it the first thing an established
 * clinic's admin would have seen after the deploy is a setup wizard.
 */
class SetupWizardTest extends TenancyTestCase
{
    public function test_a_fresh_clinic_sends_its_admin_to_the_wizard(): void
    {
        $clinic = $this->asFreshClinic($this->provisionClinic('alpha'));
        $this->staffFor($clinic, 'admin@alpha.test', StaffRole::Admin);

        /*
         * The chain, asserted as it actually happens: signing in redirects to
         * the dashboard, and the middleware turns THAT request into the wizard.
         * A browser follows both hops and lands on /setup, which is why the
         * browser check sees one redirect where the suite sees two.
         */
        $this->loginAs($clinic, 'admin@alpha.test')
            ->assertRedirect($this->url($clinic, '/dashboard'));

        $this->get($this->url($clinic, '/dashboard'))
            ->assertRedirect($this->url($clinic, '/setup'));
    }

    public function test_a_clinic_that_finished_setup_is_never_prompted_again(): void
    {
        $clinic = $this->provisionClinic('alpha');   // provisioned = marked complete
        $this->staffFor($clinic, 'admin@alpha.test', StaffRole::Admin);

        $this->loginAs($clinic, 'admin@alpha.test')
            ->assertRedirect($this->url($clinic, '/dashboard'));

        // And the dashboard stays the dashboard.
        $this->get($this->url($clinic, '/dashboard'))->assertOk();
    }

    /**
     * The condition that protects every clinic already in production.
     *
     * None of them carries the completion marker, so the marker alone would
     * have dropped each of their admins into a first-run wizard.
     */
    public function test_a_clinic_with_patients_is_never_prompted_even_without_the_marker(): void
    {
        $clinic = $this->asFreshClinic($this->provisionClinic('alpha'));
        $staff = $this->staffFor($clinic, 'admin@alpha.test', StaffRole::Admin);

        $this->inTenant($clinic, fn () => Patient::create([
            'patient_id' => 'ALPHA-P-1',
            'full_name' => 'Existing Patient',
            'date_of_birth' => '1990-01-01',
            'phone' => '08030000001',
            'blood_group' => 'O+',
            'registered_by' => $staff->id,
        ]));

        $this->assertFalse(
            $this->inTenant($clinic, fn () => ClinicSetup::shouldPrompt()),
            'A clinic with patients is in use and must never be sent to first-run setup.',
        );

        $this->loginAs($clinic, 'admin@alpha.test')
            ->assertRedirect($this->url($clinic, '/dashboard'));

        $this->get($this->url($clinic, '/dashboard'))->assertOk();
    }

    public function test_a_non_admin_cannot_reach_the_wizard(): void
    {
        $clinic = $this->asFreshClinic($this->provisionClinic('alpha'));
        $this->staffFor($clinic, 'nurse@alpha.test', StaffRole::Nurse);

        $this->post($this->url($clinic, '/login'), [
            'email' => 'nurse@alpha.test',
            'password' => 'password123',
        ]);

        $this->refreshApplication();
        $this->actingAsStaff($clinic, 'nurse@alpha.test');

        $this->get($this->url($clinic, '/setup'))->assertForbidden();
    }

    public function test_completing_the_wizard_records_the_marker(): void
    {
        $clinic = $this->asFreshClinic($this->provisionClinic('alpha'));
        $this->staffFor($clinic, 'admin@alpha.test', StaffRole::Admin);

        $this->assertNull($this->inTenant($clinic, fn () => Setting::get(Setting::SETUP_COMPLETED_AT)));

        $this->actingAsStaff($clinic, 'admin@alpha.test');
        $this->post($this->url($clinic, '/setup/complete'))
            ->assertRedirect($this->url($clinic, '/dashboard'));

        $this->assertNotNull(
            $this->inTenant($clinic, function () {
                Setting::forgetCached();

                return Setting::get(Setting::SETUP_COMPLETED_AT);
            }),
            'Finishing — or skipping — must record the marker, or the wizard returns on the next sign-in.',
        );
    }

    // ── helpers ────────────────────────────────────────────────────────────

    /** Undo the base class's "already set up", for tests that are about setup. */
    private function asFreshClinic(Clinic $clinic): Clinic
    {
        $this->inTenant($clinic, function () {
            Setting::query()->where('key', Setting::SETUP_COMPLETED_AT)->delete();
            Setting::forgetCached();
        });

        return $clinic;
    }

    private function staffFor(Clinic $clinic, string $email, StaffRole $role): Staff
    {
        return $this->inTenant($clinic, function () use ($email, $role) {
            $staff = Staff::create([
                'name' => 'Test '.$role->value,
                'email' => $email,
                'password' => 'password123',
                'role' => $role,
            ]);

            $staff->forceFill(['email_verified_at' => now()])->save();

            return $staff;
        });
    }

    private function actingAsStaff(Clinic $clinic, string $email): void
    {
        $this->refreshApplication();
        tenancy()->initialize($clinic);
        $this->actingAs(Staff::where('email', $email)->firstOrFail());
    }

    private function loginAs(Clinic $clinic, string $email)
    {
        $this->refreshApplication();

        return $this->post($this->url($clinic, '/login'), [
            'email' => $email,
            'password' => 'password123',
        ]);
    }

    private function url(Clinic $clinic, string $path): string
    {
        return "http://{$clinic->subdomain}.".config('tenancy.root_domain').$path;
    }
}
