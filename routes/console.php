<?php

use App\Console\Commands\AuditScheduler;
use App\Console\Commands\AuditTrials;
use App\Console\Commands\BackupTenants;
use App\Console\Commands\CleanupTenantBackups;
use App\Console\Commands\MonitorTenantBackups;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled work under tenancy (ARCHITECTURE.md §7)
|--------------------------------------------------------------------------
|
| Two kinds of task, and conflating them is the bug:
|
|   PER-TENANT  must iterate clinics explicitly. `backup:run` on its own backs
|               up whichever database the DEFAULT connection points at — the
|               central one, every time — while reporting success. It is a
|               failure mode indistinguishable from working.
|
|   CENTRAL     runs ONCE. Trial expiry and (later) dunning read the registry,
|               which is a central table. Running them per tenant would be N
|               identical queries and N times the notifications.
|
| Everything carries withoutOverlapping(): a backup across N tenant databases
| will outlive its minute, and the next tick must not start a second one on top
| of it. The expiry is generous — a stuck lock that clears itself in an hour is
| better than one that needs a human at 3am.
|
| Everything also records its run, so SILENCE is detectable. See
| AuditScheduler: monitoring failures alone is what let a scheduler that had
| never fired look healthy for months.
|
*/

// --- Per-tenant: one archive per clinic ---
Schedule::command(BackupTenants::class)
    ->dailyAt('02:00')
    ->withoutOverlapping(60)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/tenant-backups.log'));

/*
 * --- Per-tenant: prune each clinic's archives ---
 *
 * Was `backup:clean`, central and once. That pruned the central directory,
 * which is empty — every archive is per-tenant — so it reported "Cleanup
 * completed!" nightly while every clinic's directory grew without bound.
 */
Schedule::command(CleanupTenantBackups::class)
    ->dailyAt('01:30')
    ->withoutOverlapping(30)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/tenant-backups.log'));

/*
 * --- Per-tenant: does a recent archive actually EXIST? ---
 *
 * Runs AFTER the backup, and asks the disk rather than the run log. schedule:audit
 * below asks whether the backup task REPORTED success; this asks whether it
 * actually produced something. A run that says ✓ while writing nothing passes the
 * first check and fails this one, which is the exact failure this project has
 * already met twice.
 *
 * `backup:monitor` alone looked at the central directory and reported "there are
 * no backups of this application at all" while every clinic had one — and it was
 * not scheduled at all, while the docs said it ran here at 03:00.
 */
Schedule::command(MonitorTenantBackups::class)
    ->dailyAt('03:00')
    ->withoutOverlapping(30)
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/tenant-backups.log'));

// --- Central, once: read the registry, report on trials ---
Schedule::command(AuditTrials::class)
    ->dailyAt('06:00')
    ->withoutOverlapping(15);

/*
 * --- Silence detection ---
 *
 * Runs AFTER the tasks it audits, so a same-day backup counts. It cannot catch
 * the scheduler dying altogether — a silence detector that is itself silent
 * detects nothing — which is why the same check is exposed through /up for an
 * external monitor. This layer catches "the scheduler runs but a task stopped
 * working", which is the more common and more insidious case.
 */
Schedule::command(AuditScheduler::class)
    ->dailyAt('07:00')
    ->withoutOverlapping(15);
