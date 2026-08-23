<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform operators — CENTRAL (ARCHITECTURE.md §4.1, D5).
 *
 * Us, not clinic staff. Deliberately a separate table, model and guard from the
 * per-tenant `staff` table: keeping both concepts called "user" is how somebody
 * eventually authenticates one against the other, and the blast radius of that
 * mistake is every clinic's records at once.
 *
 * Authenticated on admin.africhartemr.com, which is a central domain and never
 * enters tenant context.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();   // deactivating an operator must not orphan their audit trail
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_admins');
    }
};
