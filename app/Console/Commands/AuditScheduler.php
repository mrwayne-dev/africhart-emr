<?php

namespace App\Console\Commands;

use App\Models\Clinic;
use App\Models\ScheduledTaskRun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Detect SILENCE — tasks that should have run and did not.
 *
 * ── The failure this exists to catch ───────────────────────────────────────
 *
 * Monitoring failures is the easy half. Before Sprint 0, `schedule:run` had
 * never executed once on the server: no cron entry existed, so nothing errored,
 * nothing logged, and the backup destination directory simply never came into
 * being. "Automated backups" was listed as a completed pre-condition while
 * being operationally dead. Nothing was failing — that was the problem.
 *
 * So this asserts a POSITIVE: that each task has a recent successful run. An
 * absent row is the alarm.
 *
 * ── The gap this cannot close on its own ───────────────────────────────────
 *
 * If the scheduler stops entirely, this command stops too, and a silence
 * detector that is itself silent detects nothing. That is why the same check is
 * exposed through the health endpoint (see AppServiceProvider's DiagnosingHealth
 * listener): an external uptime monitor polling /up becomes the dead-man's
 * switch, and it lives outside the process that could die.
 *
 * ⚠️ Wiring that external monitor is a DEPLOYMENT step, not a code one. Until
 * something polls /up, the outermost layer of this is not armed.
 */
class AuditScheduler extends Command
{
    protected $signature = 'schedule:audit {--quiet-hours=26 : Age at which a task counts as silent}';

    protected $description = 'Alert when a scheduled task has not succeeded recently';

    public const TASK = 'schedule:audit';

    /**
     * Tasks that must have succeeded recently, and the window for each.
     * Daily tasks get 26 hours, not 24 — a run drifting by an hour is normal
     * and paging someone for it teaches them to ignore the alert.
     */
    public function expectations(): array
    {
        return [
            BackupTenants::TASK => 26,

            /*
             * The monitor is itself monitored. It is the check that asks whether
             * a real archive exists on disk, so it going quiet would restore
             * exactly the blind spot it was written to remove — and it would do
             * so invisibly, because a monitor that never runs never complains.
             */
            MonitorTenantBackups::TASK => 26,

            /*
             * Cleanup silence is slower but not harmless: unpruned archives fill
             * the disk, and a full disk stops the backups it shares a volume with.
             */
            CleanupTenantBackups::TASK => 26,

            AuditTrials::TASK => 26,
        ];
    }

    public function handle(): int
    {
        $problems = $this->findSilentTasks();

        foreach ($problems as $problem) {
            $this->error('  '.$problem);
            Log::error('Scheduler silence detected', ['detail' => $problem]);
        }

        if (empty($problems)) {
            $this->info('  All scheduled tasks have run recently.');
            ScheduledTaskRun::record(self::TASK, 'succeeded', null, 'No silence detected.');

            return self::SUCCESS;
        }

        ScheduledTaskRun::record(self::TASK, 'failed', null, implode(' | ', $problems));

        return self::FAILURE;
    }

    /**
     * Public so the health endpoint asks the SAME question this command does.
     * Two implementations of "is it silent?" would eventually disagree, and the
     * one that disagreed quietly would be the one that mattered.
     *
     * @return array<int, string>
     */
    public function findSilentTasks(): array
    {
        $problems = [];

        foreach ($this->expectations() as $task => $hours) {
            if (! ScheduledTaskRun::succeededWithin($task, $hours)) {
                $last = ScheduledTaskRun::where('task', $task)->latest('ran_at')->first();

                $problems[] = $last
                    ? "[{$task}] has not succeeded since {$last->ran_at->diffForHumans()}."
                    : "[{$task}] has NEVER run.";
            }
        }

        /*
         * Per-clinic coverage, not just "the task ran". A backup that ran but
         * skipped a clinic is the same outcome as no backup for that clinic —
         * and it is exactly what a per-tenant loop can get wrong.
         */
        $backedUp = ScheduledTaskRun::query()
            ->where('task', BackupTenants::TASK)
            ->where('status', 'succeeded')
            ->where('ran_at', '>=', now()->subHours(26))
            ->pluck('clinic_id')
            ->filter()
            ->all();

        $missing = Clinic::query()
            ->whereIn('status', ['trialing', 'active', 'past_due'])
            ->whereNotIn('id', $backedUp)
            ->pluck('subdomain')
            ->all();

        if ($missing) {
            $problems[] = 'No backup in the last 26h for: '.implode(', ', $missing).'.';
        }

        return $problems;
    }
}
