<?php

namespace Tests\Tenancy;

use App\Enums\StaffRole;
use App\Models\Clinic;
use App\Models\Invoice;
use App\Models\Medication;
use App\Models\Setting;
use App\Models\Staff;
use App\Services\ConsultationService;
use App\Services\InvoiceService;
use App\Services\PatientService;
use Illuminate\Database\QueryException;
use ReflectionMethod;

/**
 * A2 — per-clinic configuration, and the identifier-collision gate.
 *
 * Before this, three services hardcoded the prefix `ACH-` while the sequence
 * counter was scoped to each clinic's own database. Verified live against two
 * real tenants on the same day: Hope (7 patients) and Grace (3) BOTH generated
 * ACH-20260828-0001, ACH-C-20260828-0001 and ACH-INV-20260828-0001.
 *
 * That is a collision, not a leak — nothing crossed between clinics — but it
 * had to close before clinic #2, because re-prefixing identifiers once records
 * exist means changing IDs already printed on invoices and quoted in support.
 */
class PerTenantConfigTest extends TenancyTestCase
{
    // ── The collision gate ─────────────────────────────────────────────────

    public function test_two_clinics_never_mint_the_same_identifiers(): void
    {
        $a = $this->provisionClinic('alpha', ['id_prefix' => 'ALFA']);
        $b = $this->provisionClinic('bravo', ['id_prefix' => 'BRVO']);

        $idsA = $this->generateIds($a);
        $idsB = $this->generateIds($b);

        foreach (['patient', 'consultation', 'invoice'] as $kind) {
            $this->assertNotSame(
                $idsA[$kind],
                $idsB[$kind],
                "Both clinics generated the same {$kind} identifier [{$idsA[$kind]}]. "
                .'This is the collision the per-clinic prefix exists to prevent.',
            );
        }

        // Not merely different — each carries its OWN clinic's prefix, so the
        // identifier says where it came from.
        $this->assertStringStartsWith('ALFA-', $idsA['patient']);
        $this->assertStringStartsWith('BRVO-', $idsB['patient']);
        $this->assertStringStartsWith('ALFA-C-', $idsA['consultation']);
        $this->assertStringStartsWith('BRVO-C-', $idsB['consultation']);
        $this->assertStringStartsWith('ALFA-INV-', $idsA['invoice']);
        $this->assertStringStartsWith('BRVO-INV-', $idsB['invoice']);
    }

    /**
     * The sequence is what made the old collision certain rather than merely
     * possible: both clinics count from 1 within their own database, so their
     * Nth record of the day lands on the same number. The prefix must therefore
     * separate them even when the counters agree exactly.
     */
    public function test_identifiers_stay_distinct_when_both_clinics_are_at_the_same_count(): void
    {
        $a = $this->provisionClinic('alpha', ['id_prefix' => 'ALFA']);
        $b = $this->provisionClinic('bravo', ['id_prefix' => 'BRVO']);

        foreach ([$a, $b] as $clinic) {
            $this->seedPatients($clinic, 3);
        }

        $idsA = $this->generateIds($a);
        $idsB = $this->generateIds($b);

        $this->assertSame(
            substr($idsA['patient'], strlen('ALFA')),
            substr($idsB['patient'], strlen('BRVO')),
            'Precondition: both clinics should be at the same sequence number, or this proves nothing.',
        );

        $this->assertNotSame($idsA['patient'], $idsB['patient']);
    }

    public function test_a_clinic_cannot_take_another_clinics_prefix(): void
    {
        $this->provisionClinic('alpha', ['id_prefix' => 'ALFA']);

        $this->expectException(QueryException::class);

        // The UNIQUE index is the point: distinctness is enforced by the
        // database, not by operators remembering to pick something else.
        $this->provisionClinic('bravo', ['id_prefix' => 'ALFA']);
    }

    public function test_a_malformed_prefix_is_refused(): void
    {
        foreach (['', 'a', 'lower', 'HAS SPACE', 'TOO-LONG-FOR-THE-COLUMN'] as $prefix) {
            try {
                $this->provisionClinic('alpha', ['id_prefix' => $prefix]);
                $this->fail("A clinic was created with the malformed prefix [{$prefix}].");
            } catch (\InvalidArgumentException) {
                // expected
            }
        }

        $this->assertSame(0, Clinic::count(), 'No clinic should have survived a malformed prefix.');
    }

    // ── Consultation fee ───────────────────────────────────────────────────

    public function test_each_clinic_bills_its_own_consultation_fee(): void
    {
        $a = $this->provisionClinic('alpha', ['id_prefix' => 'ALFA']);
        $b = $this->provisionClinic('bravo', ['id_prefix' => 'BRVO']);

        $this->inTenant($a, fn () => Setting::put(Setting::CONSULTATION_FEE, 7500));
        $this->inTenant($b, fn () => Setting::put(Setting::CONSULTATION_FEE, 12000));

        $this->assertSame('7500', $this->inTenant($a, fn () => Setting::get(Setting::CONSULTATION_FEE)));
        $this->assertSame('12000', $this->inTenant($b, fn () => Setting::get(Setting::CONSULTATION_FEE)));

        // The fee reaches the invoice, not just the settings table.
        $feeOnInvoiceA = $this->inTenant($a, function () {
            Setting::forgetCached();

            return (float) app(InvoiceService::class)->consultationFee();
        });

        $this->assertSame(7500.0, $feeOnInvoiceA);
    }

    public function test_a_clinic_without_a_fee_set_falls_back_rather_than_billing_nothing(): void
    {
        $a = $this->provisionClinic('alpha', ['id_prefix' => 'ALFA']);

        $fee = $this->inTenant($a, fn () => (float) app(InvoiceService::class)->consultationFee());

        $this->assertSame(
            (float) config('billing.consultation_fee'),
            $fee,
            'An unconfigured clinic must fall back to the platform default, never to zero.',
        );
    }

    // ── Settings isolation ─────────────────────────────────────────────────

    public function test_one_clinics_settings_are_invisible_to_another(): void
    {
        $a = $this->provisionClinic('alpha', ['id_prefix' => 'ALFA']);
        $b = $this->provisionClinic('bravo', ['id_prefix' => 'BRVO']);

        $this->inTenant($a, fn () => Setting::put(Setting::CLINIC_PHONE, '080-ALPHA'));

        $this->assertNull(
            $this->inTenant($b, fn () => Setting::get(Setting::CLINIC_PHONE)),
            "Clinic B read clinic A's setting.",
        );

        // Control: A still has it, so the null above is isolation and not a
        // failed write.
        $this->assertSame('080-ALPHA', $this->inTenant($a, fn () => Setting::get(Setting::CLINIC_PHONE)));
    }

    /**
     * The memo inside Setting is keyed by tenant, and this is why. One process
     * routinely serves several clinics; a memo keyed only by setting name would
     * hand clinic B whatever clinic A read first — exactly the bug the cache
     * bootstrapper had to be written to fix.
     */
    public function test_reading_a_setting_in_one_clinic_does_not_poison_the_next(): void
    {
        $a = $this->provisionClinic('alpha', ['id_prefix' => 'ALFA']);
        $b = $this->provisionClinic('bravo', ['id_prefix' => 'BRVO']);

        $this->inTenant($a, fn () => Setting::put(Setting::CLINIC_ADDRESS, '1 Alpha Road'));
        $this->inTenant($b, fn () => Setting::put(Setting::CLINIC_ADDRESS, '2 Bravo Street'));

        // Read A first, then B, in the same process — the order that would
        // expose a shared memo.
        $this->assertSame('1 Alpha Road', $this->inTenant($a, fn () => Setting::get(Setting::CLINIC_ADDRESS)));
        $this->assertSame('2 Bravo Street', $this->inTenant($b, fn () => Setting::get(Setting::CLINIC_ADDRESS)));
        $this->assertSame('1 Alpha Road', $this->inTenant($a, fn () => Setting::get(Setting::CLINIC_ADDRESS)));
    }

    // ── Drug catalogue ─────────────────────────────────────────────────────

    /**
     * The catalogue was already tenant-side — `medications` is in the tenant
     * migration set and absent from central, confirmed against both live
     * databases. This proves it rather than assuming it, since "already
     * scoped" was the checklist's claim and not evidence.
     */
    public function test_each_clinic_has_its_own_drug_catalogue_at_its_own_prices(): void
    {
        $a = $this->provisionClinic('alpha', ['id_prefix' => 'ALFA']);
        $b = $this->provisionClinic('bravo', ['id_prefix' => 'BRVO']);

        $this->inTenant($a, fn () => Medication::create([
            'name' => 'Amoxicillin 500mg', 'default_price' => 1200, 'is_active' => true,
        ]));

        $this->inTenant($b, fn () => Medication::create([
            'name' => 'Amoxicillin 500mg', 'default_price' => 2500, 'is_active' => true,
        ]));

        $priceA = $this->inTenant($a, fn () => (float) Medication::where('name', 'Amoxicillin 500mg')->value('default_price'));
        $priceB = $this->inTenant($b, fn () => (float) Medication::where('name', 'Amoxicillin 500mg')->value('default_price'));

        $this->assertSame(1200.0, $priceA);
        $this->assertSame(2500.0, $priceB, "Clinic B's price must be its own, not clinic A's.");

        // A drug stocked by one clinic does not appear in the other's list.
        $this->inTenant($a, fn () => Medication::create([
            'name' => 'Alpha-only Syrup', 'default_price' => 900, 'is_active' => true,
        ]));

        $this->assertSame(
            0,
            $this->inTenant($b, fn () => Medication::where('name', 'Alpha-only Syrup')->count()),
            "Clinic A's drug leaked into clinic B's catalogue.",
        );

        $this->assertSame(1, $this->inTenant($a, fn () => Medication::where('name', 'Alpha-only Syrup')->count()));
    }

    // ── Patient-facing identity ────────────────────────────────────────────

    public function test_an_invoice_names_the_clinic_that_issued_it(): void
    {
        $a = $this->provisionClinic('alpha', ['id_prefix' => 'ALFA', 'name' => 'Alpha Family Clinic']);
        $b = $this->provisionClinic('bravo', ['id_prefix' => 'BRVO', 'name' => 'Bravo Medical Centre']);

        $this->inTenant($a, function () {
            Setting::put(Setting::CLINIC_ADDRESS, '12 Aba Road, Port Harcourt');
            Setting::put(Setting::CLINIC_PHONE, '0803 111 1111');
        });

        $html = $this->renderInvoiceFor($a);

        $this->assertStringContainsString('Alpha Family Clinic', $html, 'The invoice must name the issuing clinic.');
        $this->assertStringContainsString('12 Aba Road, Port Harcourt', $html);
        $this->assertStringContainsString('0803 111 1111', $html);

        $this->assertStringNotContainsString(
            'Bravo Medical Centre',
            $html,
            "Another clinic's name must never appear on this invoice.",
        );

        /*
         * The vendor name is still allowed — but only as the "generated by"
         * credit in the footer, not as the letterhead it used to be.
         */
        $this->assertStringContainsString('Generated by AfriChart EMR', $html);
    }

    // ── helpers ────────────────────────────────────────────────────────────

    /** @return array{patient:string, consultation:string, invoice:string} */
    private function generateIds(Clinic $clinic): array
    {
        return $this->inTenant($clinic, fn () => [
            'patient' => $this->callPrivate(app(PatientService::class), 'generatePatientId'),
            'consultation' => $this->callPrivate(app(ConsultationService::class), 'generateConsultationId'),
            'invoice' => $this->callPrivate(app(InvoiceService::class), 'generateInvoiceNumber'),
        ]);
    }

    private function callPrivate(object $object, string $method): string
    {
        $reflection = new ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return (string) $reflection->invoke($object);
    }

    private function seedPatients(Clinic $clinic, int $count): void
    {
        $this->inTenant($clinic, function () use ($count) {
            $staff = Staff::create([
                'name' => 'Seeder', 'email' => 'seeder@'.tenant('subdomain').'.test',
                'password' => 'password123', 'role' => StaffRole::Admin,
            ]);

            for ($i = 1; $i <= $count; $i++) {
                app(PatientService::class)->createPatient([
                    'full_name' => "Patient {$i}",
                    'date_of_birth' => '1990-01-01',
                    'phone' => '080300000'.$i,
                    'blood_group' => 'O+',
                ], $staff->id);
            }
        });
    }

    private function renderInvoiceFor(Clinic $clinic): string
    {
        return $this->inTenant($clinic, function () {
            $staff = Staff::create([
                'name' => 'Reception', 'email' => 'reception@'.tenant('subdomain').'.test',
                'password' => 'password123', 'role' => StaffRole::Receptionist,
            ]);

            $patient = app(PatientService::class)->createPatient([
                'full_name' => 'Invoice Patient',
                'date_of_birth' => '1990-01-01',
                'phone' => '08031234567',
                'blood_group' => 'O+',
            ], $staff->id);

            $invoice = Invoice::create([
                'patient_id' => $patient->id,
                'created_by' => $staff->id,
                'invoice_number' => tenant()->idPrefix().'-INV-TEST-0001',
                'subtotal' => 5000,
                'total' => 5000,
                'status' => 'issued',
            ]);

            $invoice->load(['patient', 'consultation', 'createdBy', 'items']);

            return view('invoices.pdf', [
                'invoice' => $invoice,
                'clinicName' => tenant('name'),
                'clinicAddress' => Setting::get(Setting::CLINIC_ADDRESS),
                'clinicPhone' => Setting::get(Setting::CLINIC_PHONE),
                'clinicEmail' => Setting::get(Setting::CLINIC_EMAIL),
            ])->render();
        });
    }
}
