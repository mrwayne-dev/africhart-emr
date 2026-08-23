<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Framework tables for the CENTRAL database.
 *
 * ── Why this migration exists ──────────────────────────────────────────────
 *
 * ARCHITECTURE.md §4.1 lists jobs/job_batches/failed_jobs as central but omits
 * `sessions` and `cache`. That is a gap in the doc, not a decision: under D1 the
 * session, cache and queue drivers are all `database`, and the CENTRAL app is a
 * real app — the marketing site posts CSRF-protected forms on africhartemr.com
 * and the super-admin panel will authenticate on admin.africhartemr.com. Both
 * need a `sessions` table on the central connection. Without it every central
 * request fails the moment the driver is `database`.
 *
 * So these tables exist in BOTH databases, and that is correct rather than
 * duplication: a central session and a tenant session are different sessions,
 * which is exactly what D1 and the §6.2 isolation test require. The tenant set
 * gets its own copies from the Laravel scaffolding migrations in
 * database/migrations/tenant/.
 *
 * Deliberately dated 085000 — before plans/clinics — so a fresh central
 * database has somewhere to put a session before anything else runs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            // Nullable and unconstrained: a central session belongs to a
            // platform_admin, or to nobody at all (an anonymous visitor reading
            // the marketing site). No FK, because most rows have no owner.
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        // Central queue only: cross-tenant work — provisioning, trial expiry,
        // dunning. Tenant-scoped jobs live in the tenant's own jobs table.
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('sessions');
    }
};
