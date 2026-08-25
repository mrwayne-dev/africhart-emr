<?php

namespace Tests\Tenancy;

use App\Models\Clinic;
use App\Models\Plan;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Base class for tenancy tests. Provisions REAL tenants against REAL MySQL.
 *
 * ── Why real, and not mocks ────────────────────────────────────────────────
 *
 * The §6 guarantees are about what the database actually does when two clinics
 * exist. A mock that returns the right answer proves the mock is correct. These
 * tests create databases, migrate them, write to them, and then try to reach
 * across — because the only convincing evidence that a leak is impossible is an
 * attempted leak that fails.
 *
 * ── The safety boundary ────────────────────────────────────────────────────
 *
 * Teardown DROPS DATABASES. Three independent guards stand between that and a
 * developer's or a clinic's data:
 *
 *   1. The suite refuses to run unless the central connection is the dedicated
 *      test database (assertUsingTestDatabase). Point it at africhart_central
 *      and it aborts before creating anything.
 *   2. Test tenants use their own prefix (africhart_testtenant_), set in
 *      phpunit.tenancy.xml. It cannot collide with the dev prefix
 *      (africhart_tenant_) or anything on a server.
 *   3. Teardown only ever drops databases it created AND whose name carries the
 *      test prefix. Both conditions, not either.
 *
 * A crashed run can still orphan a database. dropStrayTestDatabases() sweeps
 * anything matching the test prefix at the start of each run, so they cannot
 * accumulate — checked once per process, not per test.
 */
abstract class TenancyTestCase extends TestCase
{
    /** Databases created by the current test, dropped in tearDown. */
    protected array $provisioned = [];

    private static bool $centralPrepared = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertUsingTestDatabase();

        if (! self::$centralPrepared) {
            $this->dropStrayTestDatabases();
            $this->prepareCentralSchema();
            self::$centralPrepared = true;
        }

        /*
         * Registry and queue state must not carry between tests. The jobs table
         * is CENTRAL and survives migrate:fresh (which runs once per process),
         * so a job left by an earlier test would be read as this test's own —
         * and that already produced one confusing failure.
         */
        Clinic::query()->delete();
        DB::table('jobs')->delete();
        DB::table('failed_jobs')->delete();
        $this->seedPlans();
    }

    protected function tearDown(): void
    {
        // Leave tenant context before dropping anything, or the connection
        // being torn down is the one still in use.
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        foreach ($this->provisioned as $database) {
            $this->dropDatabase($database);
        }

        $this->provisioned = [];

        parent::tearDown();
    }

    /**
     * Provision a real clinic: registry row, database, migrations.
     *
     * Goes through Clinic::create() rather than a factory so the genuine
     * TenantCreated pipeline runs — the same path A4 provisioning will use.
     */
    protected function provisionClinic(string $subdomain, array $attributes = []): Clinic
    {
        $clinic = Clinic::create(array_merge([
            'name' => Str::headline($subdomain).' Clinic',
            'subdomain' => $subdomain,
            'plan' => 'clinic',
            'status' => 'active',
            'owner_name' => 'Owner '.$subdomain,
            'owner_email' => "owner@{$subdomain}.test",
        ], $attributes));

        $database = $clinic->database()->getName();

        $this->assertStringStartsWith(
            $this->testPrefix(),
            $database,
            'A provisioned test tenant must carry the test prefix, or teardown will not drop it.',
        );

        $this->provisioned[] = $database;

        return $clinic;
    }

    /**
     * Seed one clinic with a full, DISTINGUISHABLE set of clinical records.
     *
     * Distinguishable is the point: every value carries the clinic's subdomain,
     * so a leak is not merely a wrong count but a visibly foreign record. A
     * test that seeds identical data into both tenants can pass while the
     * databases are shared.
     *
     * @return array{staff:int, patient:int, consultation:int, invoice:int, audit:int}
     *         the primary keys, so another tenant's context can try to reach them
     */
    protected function seedClinicalRecords(Clinic $clinic, int $patients = 3): array
    {
        return $this->inTenant($clinic, function () use ($clinic, $patients) {
            $sub = $clinic->subdomain;

            $staff = \App\Models\Staff::create([
                'name' => ucfirst($sub).' Doctor',
                'email' => "doctor@{$sub}.test",
                'password' => 'password123',
                'role' => \App\Enums\StaffRole::Doctor,
            ]);
            $staff->forceFill(['email_verified_at' => now()])->save();

            $firstPatient = null;

            for ($i = 1; $i <= $patients; $i++) {
                $patient = \App\Models\Patient::create([
                    'patient_id' => strtoupper($sub)."-P-{$i}",
                    'full_name' => ucfirst($sub)." Patient {$i}",
                    'date_of_birth' => '1990-01-01',
                    'phone' => '080300000'.$i,
                    'blood_group' => 'O+',
                    'allergies' => null,
                    'registered_by' => $staff->id,
                ]);

                $firstPatient ??= $patient;
            }

            $consultation = \App\Models\Consultation::create([
                'patient_id' => $firstPatient->id,
                'doctor_id' => $staff->id,
                'chief_complaint' => "{$sub} complaint",
                'clinical_notes' => "{$sub} notes",
                'status' => 'completed',
                'consultation_id' => strtoupper($sub).'-C-1',
            ]);

            $invoice = \App\Models\Invoice::create([
                'patient_id' => $firstPatient->id,
                'consultation_id' => $consultation->id,
                'created_by' => $staff->id,
                'invoice_number' => strtoupper($sub).'-INV-1',
                'subtotal' => 5000,
                'total' => 5000,
                'status' => 'issued',
            ]);

            $audit = \App\Models\AuditLog::create([
                'staff_id' => $staff->id,
                'user_name' => $staff->name,
                'action' => 'created',
                'model_type' => \App\Models\Patient::class,
                'model_id' => $firstPatient->id,
                'description' => "{$sub} registered a patient",
            ]);

            return [
                'staff' => $staff->id,
                'patient' => $firstPatient->id,
                'consultation' => $consultation->id,
                'invoice' => $invoice->id,
                'audit' => $audit->id,
            ];
        });
    }

    /** Run a callback inside a clinic's context, then return to central. */
    protected function inTenant(Clinic $clinic, callable $callback): mixed
    {
        tenancy()->initialize($clinic);

        try {
            return $callback();
        } finally {
            tenancy()->end();
        }
    }

    protected function testPrefix(): string
    {
        return (string) config('tenancy.database.prefix');
    }

    protected function centralDatabase(): string
    {
        return (string) config(
            'database.connections.'.config('tenancy.database.central_connection').'.database'
        );
    }

    // ── Guards and plumbing ────────────────────────────────────────────────

    /**
     * Abort unless we are pointed at the dedicated test database.
     *
     * This is what stops a misconfigured run from dropping real tenants.
     */
    private function assertUsingTestDatabase(): void
    {
        $central = $this->centralDatabase();
        $prefix = $this->testPrefix();

        if ($central !== 'africhart_testing' || ! str_contains($prefix, 'testtenant')) {
            $this->fail(
                "Refusing to run: tenancy tests drop databases and must use the dedicated test "
                ."configuration. Central is [{$central}] and the tenant prefix is [{$prefix}]. "
                .'Run with: composer test:tenancy'
            );
        }
    }

    private function prepareCentralSchema(): void
    {
        Artisan::call('migrate:fresh', [
            '--path' => 'database/migrations/central',
            '--force' => true,
        ]);
    }

    private function seedPlans(): void
    {
        if (Plan::query()->exists()) {
            return;
        }

        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\PlanSeeder', '--force' => true]);
    }

    /** Sweep tenant databases orphaned by an earlier crashed run. */
    private function dropStrayTestDatabases(): void
    {
        foreach ($this->existingTestDatabases() as $database) {
            $this->dropDatabase($database);
        }
    }

    protected function existingTestDatabases(): array
    {
        $prefix = $this->testPrefix();

        /*
         * information_schema, not `SHOW DATABASES LIKE ?`. SHOW is not a
         * prepared-statement-friendly command in MySQL — the placeholder is
         * passed through literally and the query is a syntax error. Querying
         * the catalogue keeps the prefix bound rather than interpolated.
         */
        return array_column(
            DB::select(
                'SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME LIKE ?',
                [$prefix.'%']
            ),
            'SCHEMA_NAME'
        );
    }

    private function dropDatabase(string $database): void
    {
        // Belt and braces: never drop anything without the test prefix, even if
        // it somehow reached the provisioned list.
        if (! str_starts_with($database, $this->testPrefix())) {
            return;
        }

        DB::statement('DROP DATABASE IF EXISTS `'.$database.'`');
    }
}
