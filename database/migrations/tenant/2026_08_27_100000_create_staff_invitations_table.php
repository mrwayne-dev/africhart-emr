<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TENANT — invitations to join THIS clinic's staff.
 *
 * The table is per-tenant, and that placement is the security property, not a
 * filing decision.
 *
 * It replaces four global invite codes held in .env — one per role, shared by
 * every clinic on the server and never expiring. Those made the credential
 * process-wide while its effect was per-clinic: one leaked admin code admitted
 * an admin to EVERY clinic, and each arrival looked like a legitimate signup.
 *
 * Because an invitation now lives in one clinic's database, presenting clinic
 * A's token on clinic B's subdomain finds no row — B's database simply has no
 * such record. Cross-tenant rejection is therefore STRUCTURAL: there is no
 * comparison to write, and so none for a later change to omit. The same
 * argument the §6 suite makes for cache and session isolation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_invitations', function (Blueprint $table) {
            $table->id();

            /*
             * The HASH, never the token.
             *
             * A raw token in this column would make a leaked database — or a
             * stray backup — a set of working invite links, each one good for a
             * staff account at whatever role it names. Laravel treats
             * password-reset tokens the same way, for the same reason.
             *
             * Unique, and the only way a row is ever found: the acceptance
             * route hashes what it was given and looks THAT up, so no
             * comparison is made against a stored secret at all.
             */
            $table->char('token_hash', 64)->unique();

            /*
             * Who it was issued to. Locked at acceptance rather than re-asked,
             * so forwarding the link cannot enrol a different address than the
             * admin approved.
             */
            $table->string('email')->index();
            $table->string('name')->nullable();

            /*
             * THE authoritative role. The old flow let the visitor pick their
             * own role from buttons on the register page and then supply the
             * matching code — so whoever held the admin code chose to be an
             * admin. Acceptance reads the role from here and never from the
             * request.
             */
            $table->string('role');

            // nullOnDelete: an invitation outlives the admin who sent it, and
            // losing the sender is not a reason to invalidate the invite.
            $table->foreignId('invited_by')->nullable()->constrained('staff')->nullOnDelete();

            $table->timestamp('expires_at');

            // Null means unused. Setting it is what makes an invite single-use.
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('accepted_by_staff_id')->nullable()->constrained('staff')->nullOnDelete();

            // An admin can cancel an invitation before it is used.
            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_invitations');
    }
};
