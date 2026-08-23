<?php

namespace App\Console\Commands;

use App\Models\Clinic;
use App\Models\ScheduledTaskRun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Back up every clinic's database, one at a time.
 *
 * ── Why this command exists at all ─────────────────────────────────────────
 *
 * `backup:run` on its own backs up whichever database the DEFAULT connection
 * points at. Under tenancy that is the central database, every time. It would
 * complete successfully, write an archive, report no errors — and never touch a
 * single clinic's records. A failure mode that looks exactly like success, and
 * the second time this project has met one (the first was the scheduler that
 * had never fired). Iterating tenants explicitly is the whole point.
 *
 * Each clinic gets its own archive so a restore is per-clinic. Restoring one
 * clinic must never mean touching another's data.
 *
 * One clinic's failure does not stop the rest: the loop continues and the
 * command exits non-zero at the end if any failed, so the run is both maximally
 * useful and honestly reported.
 */
class BackupTenants extends Command
{
    protected $signature = 'tenants:backup {--clinic= : Back up a single clinic by subdomain}';

    protected $description = "Back up each clinic's database individually";

    public const TASK = 'tenants:backup';

    public function handle(): int
    {
        $clinics = Clinic::query()
            ->when($this->option('clinic'), fn ($q, $s) => $q->where('subdomain', $s))
            ->get();

        if ($clinics->isEmpty()) {
            $this->warn('No clinics to back up.');

            // Recorded as a success: zero clinics backed up correctly is not a
            // failure, and leaving no row would look like silence.
            ScheduledTaskRun::record(self::TASK, 'succeeded', null, 'No clinics registered.');

            return self::SUCCESS;
        }

        $failed = 0;

        foreach ($clinics as $clinic) {
            $started = microtime(true);

            try {
                tenancy()->initialize($clinic);

                /*
                 * Point spatie at the tenant connection for this iteration.
                 *
                 * --db-name FILTERS config('backup.backup.source.databases');
                 * it does not add to it. That list is [mysql] — the central
                 * connection — so asking for `tenant` matched nothing and
                 * spatie reported "There are no files to be backed up" and
                 * exited 1. Overriding the config is what actually redirects
                 * the dump.
                 *
                 * `tenant` is the connection stancl has just pointed at THIS
                 * clinic's database, so each pass archives exactly one clinic.
                 */
                config(['backup.backup.source.databases' => ['tenant']]);

                /*
                 * --config=backup is REQUIRED, and its absence is silent.
                 *
                 * spatie's BackupCommand takes its Config object by constructor
                 * injection, resolved from the container BEFORE this runtime
                 * override happens. Without --config it never re-reads, so it
                 * dumped africhart_central for every clinic while reporting
                 * success — the precise failure §7 exists to prevent, produced
                 * by the code written to prevent it. Passing --config makes it
                 * rebuild from live config.
                 */
                $exit = Artisan::call('backup:run', [
                    '--only-db' => true,
                    '--config' => 'backup',
                    '--filename' => $clinic->subdomain.'-'.now()->format('Y-m-d-H-i-s').'.zip',
                ]);

                tenancy()->end();

                $ms = (int) ((microtime(true) - $started) * 1000);

                if ($exit === 0) {
                    ScheduledTaskRun::record(self::TASK, 'succeeded', $clinic->getTenantKey(), null, $ms);
                    $this->info("  ✓ {$clinic->subdomain}");
                } else {
                    $failed++;
                    ScheduledTaskRun::record(self::TASK, 'failed', $clinic->getTenantKey(), "backup:run exited {$exit}", $ms);
                    $this->error("  ✗ {$clinic->subdomain} — backup:run exited {$exit}");
                }
            } catch (Throwable $e) {
                // End tenancy even on failure, or the next clinic in the loop
                // inherits this one's connection.
                if (tenancy()->initialized) {
                    tenancy()->end();
                }

                $failed++;
                ScheduledTaskRun::record(
                    self::TASK, 'failed', $clinic->getTenantKey(),
                    $e->getMessage(), (int) ((microtime(true) - $started) * 1000)
                );

                Log::error('Tenant backup failed', [
                    'clinic' => $clinic->subdomain,
                    'error' => $e->getMessage(),
                ]);

                $this->error("  ✗ {$clinic->subdomain} — {$e->getMessage()}");
            }
        }

        // Restore the central source list. Anything running later in this
        // process — backup:clean, a monitor — must not inherit `tenant`, which
        // outside tenant context points nowhere.
        config(['backup.backup.source.databases' => [config('tenancy.database.central_connection')]]);

        $this->newLine();
        $this->line(sprintf('%d clinic(s), %d failed.', $clinics->count(), $failed));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
