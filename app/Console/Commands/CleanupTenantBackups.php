<?php

namespace App\Console\Commands;

use App\Models\Clinic;
use App\Models\ScheduledTaskRun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Apply the retention policy to every clinic's archives, one clinic at a time.
 *
 * ── Why this command exists at all ─────────────────────────────────────────
 *
 * `backup:clean` on its own prunes whichever directory the `local` disk points
 * at, and outside tenancy that is the CENTRAL storage path — which is empty,
 * because every archive this system produces is per-tenant. It therefore
 * cleaned nothing, every night, while reporting "Cleanup completed!". Meanwhile
 * each clinic's directory grew without bound: a daily archive per clinic, kept
 * for ever, on a single VPS disk.
 *
 * The same shape as the backup bug it sits next to — a command that runs
 * successfully against the wrong database — with a slower fuse.
 *
 * ── The trap, and why --config is passed ──────────────────────────────────
 *
 * `backup:clean` DOES take its Config by constructor injection, and Artisan
 * caches a resolved command for the life of the process. So the second clinic
 * in this loop gets the FIRST clinic's Config object unless --config forces a
 * rebuild from live config. That is the identical trap that made tenants:backup
 * archive the central database once per clinic while printing ✓ for each.
 *
 * What actually redirects the prune is tenancy rewriting
 * filesystems.disks.local.root, which spatie resolves at run time through the
 * Storage manager — not the Config object. So --config is not load-bearing for
 * correctness HERE, and that is precisely why it is easy to leave out and hard
 * to notice: it is load-bearing the moment anyone adds a run-time config
 * override to this loop, and its absence would be silent. It is passed, and
 * verified by contents rather than by exit code.
 *
 * ── What is NOT deleted ────────────────────────────────────────────────────
 *
 * spatie's DefaultStrategy never deletes the newest backup, whatever the
 * retention window says. A clinic that has stopped being backed up keeps its
 * last archive rather than having it aged out from under it — the monitor
 * (tenants:backup-monitor) is what notices that clinic has gone stale.
 */
class CleanupTenantBackups extends Command
{
    protected $signature = 'tenants:backup-clean {--clinic= : Clean a single clinic by subdomain}';

    protected $description = "Prune each clinic's old backup archives per the retention policy";

    public const TASK = 'tenants:backup-clean';

    public function handle(): int
    {
        $clinics = Clinic::query()
            ->when($this->option('clinic'), fn ($q, $s) => $q->where('subdomain', $s))
            ->get();

        if ($clinics->isEmpty()) {
            $this->warn('No clinics to clean.');

            ScheduledTaskRun::record(self::TASK, 'succeeded', null, 'No clinics registered.');

            return self::SUCCESS;
        }

        $failed = 0;

        foreach ($clinics as $clinic) {
            $started = microtime(true);

            try {
                tenancy()->initialize($clinic);

                $exit = Artisan::call('backup:clean', [
                    '--config' => 'backup',
                ]);

                tenancy()->end();

                $ms = (int) ((microtime(true) - $started) * 1000);

                if ($exit === 0) {
                    ScheduledTaskRun::record(self::TASK, 'succeeded', $clinic->getTenantKey(), null, $ms);
                    $this->info("  ✓ {$clinic->subdomain}");
                } else {
                    $failed++;
                    ScheduledTaskRun::record(self::TASK, 'failed', $clinic->getTenantKey(), "backup:clean exited {$exit}", $ms);
                    $this->error("  ✗ {$clinic->subdomain} — backup:clean exited {$exit}");
                }
            } catch (Throwable $e) {
                if (tenancy()->initialized) {
                    tenancy()->end();
                }

                $failed++;
                ScheduledTaskRun::record(
                    self::TASK, 'failed', $clinic->getTenantKey(),
                    $e->getMessage(), (int) ((microtime(true) - $started) * 1000)
                );

                Log::error('Tenant backup cleanup failed', [
                    'clinic' => $clinic->subdomain,
                    'error' => $e->getMessage(),
                ]);

                $this->error("  ✗ {$clinic->subdomain} — {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->line(sprintf('%d clinic(s), %d failed.', $clinics->count(), $failed));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
