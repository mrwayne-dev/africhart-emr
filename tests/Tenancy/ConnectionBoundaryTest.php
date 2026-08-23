<?php

namespace Tests\Tenancy;

use App\Models\Clinic;
use Illuminate\Support\Facades\DB;

/**
 * The central ↔ tenant connection boundary.
 *
 * NOT the §6 isolation suite — that lands in step 6 and proves four guarantees
 * against two provisioned tenants by attempting cross-tenant leaks. This is the
 * foundation those tests stand on: that entering tenant context swaps the
 * connection, and that leaving it swaps back.
 *
 * The swap-BACK is the one that matters most here. A connection left pointing
 * at a tenant after the request that opened it would mean the next piece of
 * work on that worker — a queued job, the next request on a persistent worker,
 * a scheduled command — silently running against the wrong clinic's database.
 * That is a cross-tenant write, and nothing about it would look like an error.
 *
 * Runs against real provisioned tenants, torn down afterwards — see
 * TenancyTestCase for the three guards that stand between teardown and real
 * data.
 */
class ConnectionBoundaryTest extends TenancyTestCase
{
    public function test_central_context_uses_the_central_database(): void
    {
        $this->assertFalse(tenancy()->initialized, 'Tenancy must not be initialised by default.');

        $this->assertSame(
            $this->centralDatabase(),
            DB::connection()->getDatabaseName(),
            'A central request must run against the central database.',
        );
    }

    public function test_central_database_holds_no_clinical_tables(): void
    {
        foreach (['patients', 'consultations', 'prescriptions', 'invoices', 'staff'] as $table) {
            $this->assertFalse(
                DB::getSchemaBuilder()->hasTable($table),
                "Central must not contain the tenant table [{$table}].",
            );
        }
    }

    public function test_entering_a_tenant_swaps_the_connection_and_leaving_restores_it(): void
    {
        $clinic = $this->provisionClinic('boundary');

        $before = DB::connection()->getDatabaseName();

        tenancy()->initialize($clinic);
        $inside = DB::connection()->getDatabaseName();

        tenancy()->end();
        $after = DB::connection()->getDatabaseName();

        $this->assertNotSame($before, $inside, 'Entering a tenant must swap off the central database.');
        $this->assertSame($clinic->database()->getName(), $inside, 'It must swap to THAT clinic.');
        $this->assertSame($before, $after, 'Leaving a tenant must restore the central connection.');
        $this->assertFalse(tenancy()->initialized, 'Tenancy must be torn down after end().');
    }

    public function test_two_clinics_resolve_to_two_different_databases(): void
    {
        $a = $this->provisionClinic('alpha');
        $b = $this->provisionClinic('bravo');

        tenancy()->initialize($a);
        $dbA = DB::connection()->getDatabaseName();
        tenancy()->end();

        tenancy()->initialize($b);
        $dbB = DB::connection()->getDatabaseName();
        tenancy()->end();

        $this->assertNotSame($dbA, $dbB, 'Two clinics must not share a database.');
    }
}
