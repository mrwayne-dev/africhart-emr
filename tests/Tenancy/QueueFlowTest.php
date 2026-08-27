<?php

namespace Tests\Tenancy;

use App\Enums\StaffRole;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\PatientQueue;
use App\Models\Staff;

/**
 * The two flows that the users → staff rename broke: check-in with a doctor,
 * and assigning a doctor to someone already in the queue.
 *
 * Both requests validated `assigned_doctor_id` with a rule naming the `users`
 * table by string literal. The rename dropped that table, so every such request
 * died with SQLSTATE[42S02] — a 500, in production, on two of the busiest
 * screens in the product. Nothing referenced the User *class*, so the rename's
 * grep found nothing to fix.
 *
 * These are HTTP tests on a real tenant subdomain rather than unit tests over
 * rules(), because the defect was only ever visible when the rule actually ran
 * against a database. Asserting that the rule string changed would prove
 * nothing at all.
 *
 * Each flow is tested twice — once accepting a real doctor, once rejecting a
 * non-doctor. The negative case is what shows the rule is still discriminating
 * and not merely no longer throwing: pointing it at a table that exists would
 * stop the 500 while quietly letting a receptionist be assigned as a doctor.
 */
class QueueFlowTest extends TenancyTestCase
{
    public function test_a_receptionist_can_check_a_patient_in_and_assign_a_doctor(): void
    {
        [$clinic, $ids] = $this->clinicWithStaffAndPatient();

        $this->loginAs($clinic, 'reception@alpha.test');

        $response = $this->post($this->url($clinic, '/queue'), [
            'patient_id' => $ids['patient'],
            'assigned_doctor_id' => $ids['doctor'],
            'reason' => 'Fever',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $entry = $this->inTenant($clinic, fn () => PatientQueue::latest('id')->first());

        $this->assertNotNull($entry, 'Check-in must have created a queue entry.');
        $this->assertSame(
            $ids['doctor'],
            $entry->assigned_doctor_id,
            'The doctor named at check-in must be the one recorded.',
        );
    }

    public function test_check_in_rejects_a_staff_member_who_is_not_a_doctor(): void
    {
        [$clinic, $ids] = $this->clinicWithStaffAndPatient();

        $this->loginAs($clinic, 'reception@alpha.test');

        $response = $this->post($this->url($clinic, '/queue'), [
            'patient_id' => $ids['patient'],
            'assigned_doctor_id' => $ids['receptionist'],   // a real staff row, wrong role
        ]);

        $response->assertSessionHasErrors('assigned_doctor_id');

        $this->assertSame(
            0,
            $this->inTenant($clinic, fn () => PatientQueue::count()),
            'A rejected check-in must not queue the patient.',
        );
    }

    public function test_a_doctor_can_be_assigned_to_a_patient_already_in_the_queue(): void
    {
        [$clinic, $ids] = $this->clinicWithStaffAndPatient();

        $entry = $this->inTenant($clinic, fn () => PatientQueue::create([
            'patient_id' => $ids['patient'],
            'checked_in_by' => $ids['receptionist'],
            'queue_number' => 1,
            'status' => 'waiting',
            'checked_in_at' => now(),
        ]));

        $this->loginAs($clinic, 'reception@alpha.test');

        $response = $this->patch($this->url($clinic, "/queue/{$entry->id}/assign"), [
            'assigned_doctor_id' => $ids['doctor'],
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertSame(
            $ids['doctor'],
            $this->inTenant($clinic, fn () => PatientQueue::find($entry->id)->assigned_doctor_id),
            'The assignment must be persisted against the queue entry.',
        );
    }

    public function test_assignment_rejects_a_staff_member_who_is_not_a_doctor(): void
    {
        [$clinic, $ids] = $this->clinicWithStaffAndPatient();

        $entry = $this->inTenant($clinic, fn () => PatientQueue::create([
            'patient_id' => $ids['patient'],
            'checked_in_by' => $ids['receptionist'],
            'queue_number' => 1,
            'status' => 'waiting',
            'checked_in_at' => now(),
        ]));

        $this->loginAs($clinic, 'reception@alpha.test');

        $response = $this->patch($this->url($clinic, "/queue/{$entry->id}/assign"), [
            'assigned_doctor_id' => $ids['receptionist'],
        ]);

        $response->assertSessionHasErrors('assigned_doctor_id');

        $this->assertNull(
            $this->inTenant($clinic, fn () => PatientQueue::find($entry->id)->assigned_doctor_id),
            'A rejected assignment must leave the queue entry untouched.',
        );
    }

    // ── helpers ────────────────────────────────────────────────────────────

    /**
     * @return array{0: Clinic, 1: array{doctor:int, receptionist:int, patient:int}}
     */
    private function clinicWithStaffAndPatient(): array
    {
        $clinic = $this->provisionClinic('alpha');

        $ids = $this->inTenant($clinic, function () {
            $doctor = $this->makeStaff('doctor@alpha.test', StaffRole::Doctor);
            $reception = $this->makeStaff('reception@alpha.test', StaffRole::Receptionist);

            $patient = Patient::create([
                'patient_id' => 'ALPHA-P-1',
                'full_name' => 'Alpha Patient',
                'date_of_birth' => '1990-01-01',
                'phone' => '08030000001',
                'blood_group' => 'O+',
                'registered_by' => $reception->id,
            ]);

            return [
                'doctor' => $doctor->id,
                'receptionist' => $reception->id,
                'patient' => $patient->id,
            ];
        });

        return [$clinic, $ids];
    }

    private function makeStaff(string $email, StaffRole $role): Staff
    {
        $staff = Staff::create([
            'name' => 'Test '.$role->value,
            'email' => $email,
            'password' => 'password123',
            'role' => $role,
        ]);

        // Not fillable, and the queue routes sit behind `verified`.
        $staff->forceFill(['email_verified_at' => now()])->save();

        return $staff;
    }

    private function loginAs($clinic, string $email): void
    {
        $this->post($this->url($clinic, '/login'), [
            'email' => $email,
            'password' => 'password123',
        ])->assertRedirect($this->url($clinic, '/dashboard'));
    }

    private function url($clinic, string $path): string
    {
        return "http://{$clinic->subdomain}.".config('tenancy.root_domain').$path;
    }
}
