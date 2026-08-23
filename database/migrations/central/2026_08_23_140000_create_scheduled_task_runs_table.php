<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A record of every scheduled task run — CENTRAL (ARCHITECTURE.md §7).
 *
 * This table exists to make SILENCE detectable.
 *
 * Before Sprint 0 the scheduler had never executed once, and nothing noticed:
 * a cron that stops firing produces no error, no log line and no failed job.
 * The absence was invisible precisely because absence has no signal. Backups
 * were "code complete and operationally dead" for months.
 *
 * Recording each run turns that into a positive fact we can assert against —
 * "a tenant backup succeeded in the last 24 hours" is answerable, where "did
 * anything fail?" is not.
 *
 * `clinic_id` is nullable: central tasks run once with no tenant, per-tenant
 * tasks record one row per clinic.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_task_runs', function (Blueprint $table) {
            $table->id();
            $table->string('task');                              // e.g. tenants:backup
            $table->string('clinic_id')->nullable()->index();    // null = central task
            $table->string('status', 20);                        // succeeded | failed
            $table->text('message')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('ran_at')->index();
            $table->timestamps();

            // The silence query: "has <task> succeeded recently?"
            $table->index(['task', 'status', 'ran_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_task_runs');
    }
};
