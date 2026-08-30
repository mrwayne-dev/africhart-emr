<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TENANT — the next-of-kin a clinic actually needs, and sex at registration.
 *
 * The clinic-day walk-through asked a receptionist to record an emergency
 * contact and found there was nowhere to put one: the patients table held only
 * name, date of birth, phone, blood group and allergies. A clinic that cannot
 * reach anybody when a patient deteriorates has a real operational gap, not a
 * cosmetic one.
 *
 * Relationship is included alongside name and phone because "who is this to
 * you" is the first thing asked on the phone, and a bare number does not
 * answer it.
 *
 * All nullable: every existing patient predates these fields, and registration
 * must not start failing for a walk-in who arrives alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('gender', 20)->nullable()->after('date_of_birth');
            $table->string('emergency_contact_name')->nullable()->after('allergies');
            $table->string('emergency_contact_phone', 20)->nullable()->after('emergency_contact_name');
            $table->string('emergency_contact_relationship', 50)->nullable()->after('emergency_contact_phone');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'gender',
                'emergency_contact_name',
                'emergency_contact_phone',
                'emergency_contact_relationship',
            ]);
        });
    }
};
