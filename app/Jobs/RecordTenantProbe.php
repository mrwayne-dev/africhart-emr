<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\Staff;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Writes an audit row into whichever clinic's database the job runs against.
 *
 * Exists to make queued-job isolation OBSERVABLE. The classic tenancy failure
 * is a job that runs against whatever connection the worker last had — so the
 * job has to leave a mark in a tenant-scoped table, and the test then checks
 * WHICH database that mark landed in. A job that merely returns a value proves
 * nothing, because the return value never touches a database.
 *
 * Deliberately carries no tenant id of its own: stancl's QueueTenancyBootstrapper
 * is what must restore the context. If the job passed its own clinic around,
 * the test would be proving the test's plumbing rather than the framework's.
 */
class RecordTenantProbe implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $marker) {}

    public function handle(): void
    {
        AuditLog::create([
            'staff_id' => Staff::query()->value('id'),
            'user_name' => 'queue-probe',
            'action' => 'created',
            'model_type' => self::class,
            'model_id' => 0,
            'description' => $this->marker,
        ]);
    }
}
