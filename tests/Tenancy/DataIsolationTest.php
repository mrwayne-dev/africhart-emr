<?php

namespace Tests\Tenancy;

use App\Models\AuditLog;
use App\Models\Consultation;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;

/**
 * §6.1 — DATA ISOLATION.
 *
 * > Given clinics A and B each holding patients, no query executed in A's
 * > context can return a record belonging to B.
 *
 * Every test here ATTEMPTS the leak. Asserting that A can read A's own data
 * proves nothing about isolation — a single shared database passes that. What
 * has to fail is A reaching for B.
 */
class DataIsolationTest extends TenancyTestCase
{
    public function test_the_two_clinics_are_physically_separate_databases(): void
    {
        $a = $this->provisionClinic('alpha');
        $b = $this->provisionClinic('bravo');

        $dbA = $this->inTenant($a, fn () => DB::connection()->getDatabaseName());
        $dbB = $this->inTenant($b, fn () => DB::connection()->getDatabaseName());

        $this->assertNotSame($dbA, $dbB, 'Two clinics must not share a database.');
        $this->assertSame($a->database()->getName(), $dbA);
        $this->assertSame($b->database()->getName(), $dbB);

        // Evidence: the databases exist as separate schemas in MySQL.
        $schemas = DB::select(
            'SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME IN (?, ?)',
            [$dbA, $dbB]
        );
        $this->assertCount(2, $schemas, 'Both tenant schemas must exist independently.');
    }

    public function test_counts_in_one_clinic_never_include_the_other(): void
    {
        $a = $this->provisionClinic('alpha');
        $b = $this->provisionClinic('bravo');

        $this->seedClinicalRecords($a, patients: 3);
        $this->seedClinicalRecords($b, patients: 7);

        // A sees exactly its own 3 — not 10, which is what a shared table gives.
        $this->inTenant($a, function () {
            $this->assertSame(3, Patient::count(), "A must see only its own patients.");
            $this->assertSame(1, Staff::count());
            $this->assertSame(1, Consultation::count());
            $this->assertSame(1, Invoice::count());

            /*
             * Audit rows are NOT 1. HasAuditTrail logs every model write, so
             * seeding produces one per patient, consultation and invoice, plus
             * the explicit entry — 6 in total. Asserting 1 was my error, and it
             * matters that the count is checked against reality rather than
             * relaxed to a range: an unexpectedly HIGH audit count is exactly
             * what a cross-tenant leak would look like here.
             */
            $this->assertSame(6, AuditLog::count(), 'A must see its own audit trail and no other.');

            // And every row it can see belongs to it — the real assertion.
            foreach (Patient::pluck('full_name') as $name) {
                $this->assertStringStartsWith('Alpha', $name, "A must not see [{$name}].");
            }

            foreach (AuditLog::pluck('description') as $description) {
                $this->assertStringNotContainsStringIgnoringCase(
                    'bravo', $description, "LEAK: A's audit trail contains [{$description}]."
                );
            }
        });

        $this->inTenant($b, function () {
            $this->assertSame(7, Patient::count(), "B must see only its own patients.");

            foreach (Patient::pluck('full_name') as $name) {
                $this->assertStringStartsWith('Bravo', $name, "B must not see [{$name}].");
            }
        });
    }

    public function test_a_clinic_cannot_reach_the_others_records_by_primary_key(): void
    {
        $a = $this->provisionClinic('alpha');
        $b = $this->provisionClinic('bravo');

        // Seed B FIRST with a big offset so its ids cannot be mistaken for A's.
        $this->seedClinicalRecords($b, patients: 7);
        $bKeys = $this->inTenant($b, fn () => [
            'patient' => Patient::latest('id')->first()->id,
            'consultation' => Consultation::latest('id')->first()->id,
            'invoice' => Invoice::latest('id')->first()->id,
            'audit' => AuditLog::latest('id')->first()->id,
            'staff' => Staff::latest('id')->first()->id,
        ]);

        $this->seedClinicalRecords($a, patients: 3);

        // THE LEAK ATTEMPT: from inside A, go after B's records by their exact
        // primary keys. Not a filtered query — direct key access, the strongest
        // form of the attempt.
        $this->inTenant($a, function () use ($bKeys) {
            foreach ([
                Patient::class => $bKeys['patient'],
                Consultation::class => $bKeys['consultation'],
                Invoice::class => $bKeys['invoice'],
                AuditLog::class => $bKeys['audit'],
                Staff::class => $bKeys['staff'],
            ] as $model => $id) {
                $found = $model::find($id);

                /*
                 * `find` may return a row at the same numeric id — both clinics
                 * start at 1 — so identity is not enough. What must be true is
                 * that whatever comes back is A's, never B's.
                 */
                if ($found !== null) {
                    /*
                     * An explicit per-model signature. My first version chained
                     * ?? across fields and picked Invoice's consultation_id —
                     * an integer FK — before invoice_number, so the assertion
                     * compared against "1" and failed on a record that was
                     * A's own. A test that fails for the wrong reason is only
                     * one lucky coincidence away from PASSING for the wrong
                     * reason.
                     */
                    $signature = match ($model) {
                        Patient::class => $found->full_name,
                        Consultation::class => $found->consultation_id,
                        Invoice::class => $found->invoice_number,
                        AuditLog::class => $found->description,
                        Staff::class => $found->name,
                    };

                    $this->assertStringContainsStringIgnoringCase(
                        'alpha',
                        (string) $signature,
                        "LEAK: from A, [{$model}#{$id}] returned [{$signature}] — that is B's record.",
                    );
                    $this->assertStringNotContainsStringIgnoringCase('bravo', (string) $signature);
                }
            }
        });
    }

    public function test_a_raw_cross_database_query_from_a_tenant_is_the_only_way_to_see_the_other(): void
    {
        $a = $this->provisionClinic('alpha');
        $b = $this->provisionClinic('bravo');

        $this->seedClinicalRecords($a, patients: 3);
        $this->seedClinicalRecords($b, patients: 7);

        $dbB = $b->database()->getName();

        /*
         * The point of this test is what it takes to break isolation: not a bug
         * in a scope, not a forgotten `where`, but a deliberate fully-qualified
         * cross-database query naming the other clinic's schema. Nothing the
         * application does can produce that by accident, which is exactly the
         * argument DB-per-tenant is making.
         *
         * Recorded so that if a future change makes ORDINARY queries able to see
         * across, the contrast is documented rather than assumed.
         */
        $this->inTenant($a, function () use ($dbB) {
            $ordinary = Patient::count();
            $this->assertSame(3, $ordinary);

            $deliberate = DB::selectOne("SELECT COUNT(*) AS c FROM `{$dbB}`.patients")->c;
            $this->assertSame(7, (int) $deliberate,
                'Reaching B requires naming B\'s schema explicitly — which the app never does.');
        });
    }

    public function test_central_holds_no_clinical_data_at_all(): void
    {
        $a = $this->provisionClinic('alpha');
        $this->seedClinicalRecords($a, patients: 3);

        // Back on central after the seed.
        $this->assertFalse(tenancy()->initialized);

        foreach (['patients', 'consultations', 'invoices', 'audit_logs', 'staff'] as $table) {
            $this->assertFalse(
                DB::getSchemaBuilder()->hasTable($table),
                "Central must not contain [{$table}] — clinical data lives only in tenant databases.",
            );
        }
    }
}
