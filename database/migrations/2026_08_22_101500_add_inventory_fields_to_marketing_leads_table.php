<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fields the page inventory requires the two lead forms to collect.
 *
 * `plan` is the one that matters beyond marketing: it is the chosen tier
 * carried over from /pricing, and it is what provisioning (Phase 2 A4) and
 * billing will read when a lead becomes a tenant. The other two are
 * qualifying fields that shorten the first call.
 *
 * CENTRAL table — see the create migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_leads', function (Blueprint $table) {
            $table->string('plan', 20)->nullable()->after('type');            // 'starter' | 'clinic' | 'group'
            $table->string('preferred_time', 20)->nullable()->after('doctors');
            $table->string('heard_from', 40)->nullable()->after('preferred_time');
        });
    }

    public function down(): void
    {
        Schema::table('marketing_leads', function (Blueprint $table) {
            $table->dropColumn(['plan', 'preferred_time', 'heard_from']);
        });
    }
};
