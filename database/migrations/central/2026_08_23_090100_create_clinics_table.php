<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The clinic registry — CENTRAL (ARCHITECTURE.md §4.1).
 *
 * This IS stancl's tenant table; App\Models\Clinic is the configured
 * `tenancy.tenant_model`. Two package details shape the schema:
 *
 * 1. `id` is a string, not an auto-increment. stancl generates a UUID and it
 *    becomes part of the tenant database name (africhart_tenant_<id>), so it
 *    has to be stable and portable rather than positional.
 *
 * 2. `data` is required. stancl models use stancl/virtualcolumn: any attribute
 *    NOT declared in Clinic::getCustomColumns() is JSON-encoded into this
 *    column instead of erroring. Every real column below is declared there —
 *    if the two lists drift, the attribute silently moves into `data` and
 *    queries against the column quietly return nothing.
 *
 * `tenancy_db_name` rather than the `database` the doc names: that prefix is
 * stancl's internal-key convention (HasInternalKeys::internalPrefix() ===
 * 'tenancy_'), and DatabaseConfig::getName() reads exactly this attribute. Same
 * information, under the name the package actually contracts on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinics', function (Blueprint $table) {
            $table->string('id')->primary();

            $table->string('name');
            $table->string('subdomain')->unique();
            $table->string('tenancy_db_name')->nullable();

            // provisioning · trialing · active · past_due · suspended · cancelled
            $table->string('status', 20)->default('provisioning')->index();

            $table->string('plan', 20);

            $table->string('owner_name');
            $table->string('owner_email');     // the primary comms channel — see §4.1
            $table->string('owner_phone', 30)->nullable();

            $table->timestamp('trial_ends_at')->nullable();

            $table->json('data')->nullable();  // stancl virtual columns — see above
            $table->timestamps();

            $table->foreign('plan')->references('slug')->on('plans')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinics');
    }
};
