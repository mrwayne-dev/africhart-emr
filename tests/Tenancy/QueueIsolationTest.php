<?php

namespace Tests\Tenancy;

use App\Jobs\RecordTenantProbe;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

/**
 * §6.4 — QUEUED-JOB ISOLATION.
 *
 * > A job dispatched in A's context executes against A's database, whichever
 * > worker picks it up.
 *
 * The classic failure is a job that runs against whatever connection the worker
 * happened to have last — so a job queued by clinic A gets processed while the
 * worker is "in" clinic B, and writes A's data into B. It is invisible in
 * development, where there is only ever one tenant, and it corrupts two clinics
 * at once in production.
 *
 * Architecture worth stating, since the tests assume it: jobs are stored in the
 * CENTRAL jobs table with a tenant_id in the payload, and stancl's
 * QueueTenancyBootstrapper re-initialises that tenant before the job runs. One
 * worker serves every clinic — which is what makes the failure above possible,
 * and therefore worth proving against.
 */
class QueueIsolationTest extends TenancyTestCase
{
    public function test_the_queue_uses_the_database_driver_not_sync(): void
    {
        /*
         * A guard on the harness itself. Under `sync` a job runs inline, in the
         * dispatching context, and never round-trips through a payload — so
         * every test below would pass while proving nothing about serialisation
         * or about a worker restoring context.
         */
        $this->assertSame('database', config('queue.default'),
            'Queue isolation cannot be tested under the sync driver: jobs would never be serialised.');
    }

    /**
     * The dispatch-timing trap, pinned down as its own test because it is a
     * PRODUCTION hazard, not a testing artefact.
     *
     * Job::dispatch() returns a PendingDispatch which pushes the job in its
     * DESTRUCTOR. So `fn () => Job::dispatch(...)` — an arrow function, which
     * returns — hands the object out of the tenant-scoped closure, and it is
     * destroyed only after tenancy has ended. The payload is then built with no
     * tenant, the job runs centrally, and it fails or writes to the wrong place.
     *
     * Any application code shaped like `return SomeJob::dispatch(...)` inside a
     * tenant-scoped callback has the same bug. My own first version of these
     * tests had it, which is how it was found.
     */
    public function test_a_dispatch_that_escapes_tenant_context_loses_its_tenant(): void
    {
        $a = $this->provisionClinic('alpha');
        $this->seedClinicalRecords($a, patients: 1);

        DB::table('jobs')->delete();

        // Correct: the PendingDispatch is destroyed inside the closure body.
        $this->inTenant($a, function () { RecordTenantProbe::dispatch('inside'); });
        $good = json_decode(DB::table('jobs')->orderByDesc('id')->value('payload'), true);
        $this->assertSame($a->getTenantKey(), $good['tenant_id'] ?? null,
            'A dispatch completed inside tenant context must carry its tenant.');

        DB::table('jobs')->delete();

        // The trap: returned from an arrow fn, so destroyed after tenancy ended.
        $this->inTenant($a, fn () => RecordTenantProbe::dispatch('escaped'));
        $bad = json_decode(DB::table('jobs')->orderByDesc('id')->value('payload'), true);
        $this->assertArrayNotHasKey('tenant_id', $bad,
            'Documents the trap: a PendingDispatch destroyed outside tenant context has no tenant. '
            .'If this ever starts passing a tenant, the framework has changed and the warning can go.');
    }

    public function test_a_job_dispatched_in_one_clinic_carries_that_clinic_in_its_payload(): void
    {
        $a = $this->provisionClinic('alpha');
        $this->seedClinicalRecords($a, patients: 1);

        $this->inTenant($a, function () { RecordTenantProbe::dispatch('probe-alpha'); });

        // Jobs queue centrally; the tenant travels in the payload.
        $payload = json_decode(DB::table('jobs')->value('payload'), true);

        $this->assertArrayHasKey('tenant_id', $payload,
            'Without tenant_id in the payload a worker cannot know which clinic a job belongs to.');
        $this->assertSame($a->getTenantKey(), $payload['tenant_id']);
    }

    public function test_a_job_writes_to_its_own_clinic_and_not_the_other(): void
    {
        $a = $this->provisionClinic('alpha');
        $b = $this->provisionClinic('bravo');
        $this->seedClinicalRecords($a, patients: 1);
        $this->seedClinicalRecords($b, patients: 1);

        $beforeA = $this->auditCount($a);
        $beforeB = $this->auditCount($b);

        $this->inTenant($a, function () { RecordTenantProbe::dispatch('QUEUE-PROBE-ALPHA'); });

        $this->work();

        $this->assertSame($beforeA + 1, $this->auditCount($a), "A must have gained the probe row.");
        $this->assertSame($beforeB, $this->auditCount($b), "LEAK: B gained a row from A's job.");

        // And the row that landed is the right one.
        $markers = $this->inTenant($a, fn () => AuditLog::where('user_name', 'queue-probe')->pluck('description')->all());
        $this->assertSame(['QUEUE-PROBE-ALPHA'], $markers);

        $inB = $this->inTenant($b, fn () => AuditLog::where('user_name', 'queue-probe')->count());
        $this->assertSame(0, $inB, "LEAK: A's queue probe is present in B.");
    }

    public function test_jobs_from_two_clinics_processed_together_do_not_cross(): void
    {
        $a = $this->provisionClinic('alpha');
        $b = $this->provisionClinic('bravo');
        $this->seedClinicalRecords($a, patients: 1);
        $this->seedClinicalRecords($b, patients: 1);

        // Interleaved on purpose — one worker will drain them back to back,
        // which is precisely when leaked context shows up.
        $this->inTenant($a, function () { RecordTenantProbe::dispatch('FROM-ALPHA-1'); });
        $this->inTenant($b, function () { RecordTenantProbe::dispatch('FROM-BRAVO-1'); });
        $this->inTenant($a, function () { RecordTenantProbe::dispatch('FROM-ALPHA-2'); });
        $this->inTenant($b, function () { RecordTenantProbe::dispatch('FROM-BRAVO-2'); });

        $this->work();

        $inA = $this->inTenant($a, fn () => AuditLog::where('user_name', 'queue-probe')->pluck('description')->sort()->values()->all());
        $inB = $this->inTenant($b, fn () => AuditLog::where('user_name', 'queue-probe')->pluck('description')->sort()->values()->all());

        $this->assertSame(['FROM-ALPHA-1', 'FROM-ALPHA-2'], $inA, "A must hold only its own two probes.");
        $this->assertSame(['FROM-BRAVO-1', 'FROM-BRAVO-2'], $inB, "B must hold only its own two probes.");

        // Explicitly: nothing of B's reached A, and nothing of A's reached B.
        $this->assertEmpty(array_intersect($inA, $inB), 'No probe may appear in both clinics.');
    }

    public function test_a_job_runs_against_its_own_clinic_even_while_the_worker_is_inside_another(): void
    {
        $a = $this->provisionClinic('alpha');
        $b = $this->provisionClinic('bravo');
        $this->seedClinicalRecords($a, patients: 1);
        $this->seedClinicalRecords($b, patients: 1);

        $this->inTenant($a, function () { RecordTenantProbe::dispatch('DISPATCHED-IN-ALPHA'); });

        $beforeB = $this->auditCount($b);

        /*
         * THE CLASSIC FAILURE, staged deliberately.
         *
         * Put the process INSIDE clinic B, then process a job that belongs to
         * clinic A. If the job simply used "whatever connection is current" it
         * would write A's data into B — silently, and into two clinics' records
         * at once. The job must restore its OWN tenant from the payload.
         */
        tenancy()->initialize($b);
        $this->assertSame($b->database()->getName(), DB::connection()->getDatabaseName());

        $this->work();

        tenancy()->end();

        $this->assertSame(
            0,
            $this->inTenant($b, fn () => AuditLog::where('description', 'DISPATCHED-IN-ALPHA')->count()),
            "LEAK: a job dispatched in A wrote into B because the worker was inside B.",
        );

        $this->assertSame(
            1,
            $this->inTenant($a, fn () => AuditLog::where('description', 'DISPATCHED-IN-ALPHA')->count()),
            "The job must write to the clinic that dispatched it.",
        );

        $this->assertSame($beforeB, $this->auditCount($b), "B's audit trail must be untouched.");
    }

    public function test_tenancy_is_torn_down_after_the_queue_drains(): void
    {
        $a = $this->provisionClinic('alpha');
        $this->seedClinicalRecords($a, patients: 1);

        $this->inTenant($a, function () { RecordTenantProbe::dispatch('probe'); });

        $this->work();

        /*
         * A worker that finishes a job still "inside" a tenant is the same
         * defect as the connection swap-back: the NEXT job, or the next thing
         * the process does, inherits a clinic it was never given.
         */
        $this->assertFalse(tenancy()->initialized, 'Tenancy must not be left initialised after the queue drains.');
    }

    // ── helpers ────────────────────────────────────────────────────────────

    private function work(): void
    {
        Artisan::call('queue:work', [
            '--stop-when-empty' => true,
            '--tries' => 1,
        ]);
    }

    private function auditCount($clinic): int
    {
        return $this->inTenant($clinic, fn () => AuditLog::count());
    }
}
