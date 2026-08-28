<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TENANT — one clinic's own configuration (ARCHITECTURE §4.2).
 *
 * Replaces the values that were global config and therefore identical for every
 * clinic on the server: the consultation fee from config/billing.php, and the
 * clinic's own contact details, which did not exist anywhere at all — the
 * invoice a patient receives was branded "AfriChart EMR" and never named the
 * clinic that issued it.
 *
 * Key/value rather than a column per setting. These are a handful of operator-
 * editable values that will grow (B4's Settings hub adds more), and a migration
 * per new preference is friction with no benefit — there is nothing to join on
 * and nothing to constrain.
 *
 * NOT here: the identifier prefix. It needs cross-clinic uniqueness, which
 * nothing in a per-tenant table can enforce, so it lives on central `clinics`
 * behind a unique index. See that migration for the reasoning.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
