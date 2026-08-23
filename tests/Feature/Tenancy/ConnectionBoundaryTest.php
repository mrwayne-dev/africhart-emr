<?php

namespace Tests\Feature\Tenancy;

use App\Models\Clinic;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

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
 * Runs against whatever clinics exist; skips cleanly when none do, so it never
 * reports a false pass on an empty database.
 */
class ConnectionBoundaryTest extends TestCase
{
    /*
     * phpunit.xml pins the suite to sqlite :memory:, which cannot hold the
     * central registry or two provisioned tenant databases. Rather than
     * false-pass against an empty in-memory schema, this class skips unless it
     * is genuinely running against the MySQL central database.
     *
     * ⚠️ Step 6 needs a real answer to this. The isolation suite has to
     * provision two tenants and prove leaks fail, which sqlite cannot do — it
     * will need a dedicated MySQL test database and a tenant teardown strategy.
     * Deciding that is part of step 6, not something to bodge here.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped(
                'Tenancy boundary tests need MySQL; the suite is pinned to sqlite. See step 6.'
            );
        }
    }

    public function test_central_context_uses_the_central_database(): void
    {
        $this->assertFalse(tenancy()->initialized, 'Tenancy must not be initialised by default.');

        $this->assertSame(
            config('database.connections.'.config('tenancy.database.central_connection').'.database'),
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
        $clinic = Clinic::first();

        if (! $clinic) {
            $this->markTestSkipped('No clinic provisioned; run this against a database with at least one.');
        }

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
        $clinics = Clinic::limit(2)->get();

        if ($clinics->count() < 2) {
            $this->markTestSkipped('Needs two provisioned clinics.');
        }

        [$a, $b] = [$clinics[0], $clinics[1]];

        tenancy()->initialize($a);
        $dbA = DB::connection()->getDatabaseName();
        tenancy()->end();

        tenancy()->initialize($b);
        $dbB = DB::connection()->getDatabaseName();
        tenancy()->end();

        $this->assertNotSame($dbA, $dbB, 'Two clinics must not share a database.');
    }
}
