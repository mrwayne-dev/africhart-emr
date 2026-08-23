<?php

namespace App\Console\Commands;

use App\Models\Clinic;
use App\Models\ScheduledTaskRun;
use Illuminate\Console\Command;

/**
 * Report on trials that are ending or have ended. CENTRAL, runs ONCE.
 *
 * The archetypal cross-tenant task: it reads the registry, which is a central
 * table. Running it per tenant would be N identical queries against the same
 * rows, and N times the notifications.
 *
 * ⚠️ Deliberately REPORTS ONLY — it does not change any clinic's status. The
 * lifecycle (trialing → active → past_due → suspended) belongs to B1 along with
 * the billing that drives it, and a trial silently lapsing a clinic into
 * read-only before there is any way to pay would be worse than useless. This
 * gives B1 the signal it will act on, and gives us the central-once task §7
 * requires, without pre-empting a decision that is not made yet.
 */
class AuditTrials extends Command
{
    protected $signature = 'clinics:audit-trials {--days=7 : How far ahead to look}';

    protected $description = 'Report clinics whose trial is ending or has ended (central, once)';

    public const TASK = 'clinics:audit-trials';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $expired = Clinic::query()
            ->where('status', 'trialing')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->get();

        $ending = Clinic::query()
            ->where('status', 'trialing')
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [now(), now()->addDays($days)])
            ->get();

        foreach ($expired as $clinic) {
            $this->warn("  expired : {$clinic->subdomain} (ended {$clinic->trial_ends_at->diffForHumans()})");
        }

        foreach ($ending as $clinic) {
            $this->line("  ending  : {$clinic->subdomain} ({$clinic->trial_ends_at->diffForHumans()})");
        }

        $message = sprintf('%d expired, %d ending within %d days.', $expired->count(), $ending->count(), $days);

        $this->newLine();
        $this->info($message);

        ScheduledTaskRun::record(self::TASK, 'succeeded', null, $message);

        return self::SUCCESS;
    }
}
