<?php

namespace App\Console\Commands;

use App\Models\Clinic;
use App\Models\ScheduledTaskRun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Assert that every clinic has a recent, healthy backup ARCHIVE on disk.
 *
 * ── Why this command exists at all ─────────────────────────────────────────
 *
 * `backup:monitor` on its own inspects whichever directory the `local` disk
 * points at, and outside tenancy that is the CENTRAL storage path — where
 * nothing is ever written, because every archive this system produces is
 * per-tenant. So it reported:
 *
 *     "There are no backups of this application at all."
 *
 * ...while seven healthy archives sat on the box. Worse, it was not scheduled
 * anywhere, and docs/BACKUPS_AND_OPS.md claimed it ran nightly at 03:00. A
 * health check that does not run, would report the wrong answer if it did, and
 * has documentation vouching for it, is worse than no health check: it spends
 * the trust that would otherwise go to looking.
 *
 * ── Why there is no --config here, unlike tenants:backup ───────────────────
 *
 * `backup:monitor` has no --config option, and does not need one. What changes
 * per tenant is not the backup CONFIG but the DISK ROOT: stancl's
 * FilesystemTenancyBootstrapper rewrites filesystems.disks.local.root on every
 * tenancy initialisation, and spatie resolves that disk at run time through the
 * Storage manager. Initialising tenancy is therefore sufficient, and verified —
 * the same command returns "unhealthy, no backups at all" centrally and
 * "healthy" inside a clinic, against the identical config.
 *
 * (tenants:backup needs --config for a different reason: it overrides
 * backup.backup.source.databases at run time, and spatie takes that Config by
 * constructor injection. Nothing is overridden here, so nothing is stale.)
 *
 * ── How this differs from schedule:audit, and why both exist ───────────────
 *
 * schedule:audit asks "did the task report success?" — it reads
 * scheduled_task_runs. This asks "does a recent archive actually EXIST?" — it
 * reads the disk. They fail apart: a backup run that reports ✓ while writing
 * nothing satisfies the first and fails the second, and that exact shape of
 * failure has already happened twice on this project. The row is a claim; the
 * archive is the evidence.
 *
 * This command's own runs are recorded, so schedule:audit (and through it, /up)
 * detects it going silent. A monitor nobody monitors is the first thing to die
 * quietly.
 */
class MonitorTenantBackups extends Command
{
    protected $signature = 'tenants:backup-monitor {--clinic= : Monitor a single clinic by subdomain}';

    protected $description = 'Check that every clinic has a recent, healthy backup archive';

    public const TASK = 'tenants:backup-monitor';

    public function handle(): int
    {
        $clinics = Clinic::query()
            ->when($this->option('clinic'), fn ($q, $s) => $q->where('subdomain', $s))
            ->get();

        if ($clinics->isEmpty()) {
            $this->warn('No clinics to monitor.');

            ScheduledTaskRun::record(self::TASK, 'succeeded', null, 'No clinics registered.');

            return self::SUCCESS;
        }

        $unhealthy = [];

        foreach ($clinics as $clinic) {
            $started = microtime(true);

            try {
                tenancy()->initialize($clinic);

                $exit = Artisan::call('backup:monitor');
                $output = trim(Artisan::output());

                tenancy()->end();

                $ms = (int) ((microtime(true) - $started) * 1000);

                if ($exit === 0) {
                    ScheduledTaskRun::record(self::TASK, 'succeeded', $clinic->getTenantKey(), null, $ms);
                    $this->info("  ✓ {$clinic->subdomain}");
                } else {
                    $unhealthy[] = $clinic->subdomain;

                    // Keep spatie's own failure text: it names the health check
                    // that tripped (age, size), which is the difference between
                    // "no backup" and "backup too big" at 3am.
                    ScheduledTaskRun::record(self::TASK, 'failed', $clinic->getTenantKey(), $output, $ms);

                    Log::error('Tenant backup unhealthy', [
                        'clinic' => $clinic->subdomain,
                        'detail' => $output,
                    ]);

                    $this->error("  ✗ {$clinic->subdomain}");

                    foreach (explode("\n", $output) as $line) {
                        if (trim($line) !== '') {
                            $this->line('      '.trim($line));
                        }
                    }
                }
            } catch (Throwable $e) {
                // End tenancy even on failure, or the next clinic in the loop
                // inherits this one's connection and disk root.
                if (tenancy()->initialized) {
                    tenancy()->end();
                }

                $unhealthy[] = $clinic->subdomain;

                ScheduledTaskRun::record(
                    self::TASK, 'failed', $clinic->getTenantKey(),
                    $e->getMessage(), (int) ((microtime(true) - $started) * 1000)
                );

                Log::error('Tenant backup monitor failed', [
                    'clinic' => $clinic->subdomain,
                    'error' => $e->getMessage(),
                ]);

                $this->error("  ✗ {$clinic->subdomain} — {$e->getMessage()}");
            }
        }

        $this->newLine();

        if ($unhealthy) {
            $this->error(sprintf(
                '%d of %d clinic(s) have no healthy backup: %s',
                count($unhealthy), $clinics->count(), implode(', ', $unhealthy)
            ));

            return self::FAILURE;
        }

        $this->line(sprintf('%d clinic(s), all with a recent backup.', $clinics->count()));

        return self::SUCCESS;
    }
}
