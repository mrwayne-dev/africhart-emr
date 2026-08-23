<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * One execution of one scheduled task. CENTRAL.
 *
 * CentralConnection is load-bearing: per-tenant tasks record their run from
 * INSIDE tenant context, and without this the row would be written into the
 * clinic's own database — where the silence check, which runs centrally, would
 * never find it. The task would then look silent while working perfectly.
 */
#[Fillable(['task', 'clinic_id', 'status', 'message', 'duration_ms', 'ran_at'])]
class ScheduledTaskRun extends Model
{
    use CentralConnection;

    protected function casts(): array
    {
        return ['ran_at' => 'datetime'];
    }

    public static function record(string $task, string $status, ?string $clinicId = null, ?string $message = null, ?int $ms = null): self
    {
        return static::create([
            'task' => $task,
            'clinic_id' => $clinicId,
            'status' => $status,
            'message' => $message,
            'duration_ms' => $ms,
            'ran_at' => now(),
        ]);
    }

    /** Did this task succeed within the window? The silence question. */
    public static function succeededWithin(string $task, int $hours): bool
    {
        return static::query()
            ->where('task', $task)
            ->where('status', 'succeeded')
            ->where('ran_at', '>=', now()->subHours($hours))
            ->exists();
    }
}
