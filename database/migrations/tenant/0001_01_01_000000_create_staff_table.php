<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TENANT — the clinic's own people, plus the framework tables that belong to
 * one clinic's session and password-reset state.
 *
 * `staff`, not `users` (ARCHITECTURE.md D5, §5). The clinic's people are staff
 * OF THAT CLINIC; platform operators are a separate concept in the central
 * `platform_admins` table. Keeping both called "user" is how somebody
 * eventually authenticates one against the other, and that mistake reaches
 * every clinic at once rather than one.
 *
 * All three tables move as a unit. `sessions` in particular must NOT be left
 * behind in central — a shared session table is the cross-tenant leak §6.2
 * exists to prevent.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            /*
             * DELIBERATELY still `user_id`, not renamed with the table.
             *
             * Laravel HARDCODES this column name: DatabaseSessionHandler::
             * addUserInformation() writes $payload['user_id'] with no way to
             * configure it. Renaming it to staff_id makes every session write
             * fail with an SQL error the moment SESSION_DRIVER=database — which
             * is D1, i.e. always in production.
             *
             * Nullable and unconstrained (an index, not a foreign key) because
             * most sessions have no authenticated owner at all.
             */
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
