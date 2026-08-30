<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * TENANT — let a prescription REFERENCE the catalogue, without forcing it to.
 *
 * `medication_name` was a bare string with nothing behind it, so the drug
 * catalogue was decorative: the walk-through prescribed "Notarealdrug Zzyzx
 * 500mg" and it was accepted, and renaming a catalogue entry would silently
 * desynchronise every prescription that had copied its old name.
 *
 * Both halves are deliberate:
 *
 *   medication_id  set when the doctor picks from the catalogue. Structured,
 *                  so a rename stays consistent and the catalogue becomes a
 *                  real reference rather than a suggestion list.
 *   medication_name always kept. It is what was actually prescribed, in the
 *                  words on the prescription, and it is the ONLY record for a
 *                  drug the clinic does not stock. Doctors must be able to
 *                  prescribe off-catalogue; a system that forbids it is one
 *                  they will route around.
 *
 * nullOnDelete, not cascade: removing a drug from the catalogue must never
 * delete a prescription. The prescription is a clinical record of what a
 * patient was told to take, and it outlives the catalogue entry — it simply
 * reverts to free text.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->foreignId('medication_id')
                ->nullable()
                ->after('prescribed_by')
                ->constrained('medications')
                ->nullOnDelete();
        });

        /*
         * Backfill: existing rows are all free text. Where the name matches a
         * catalogue entry exactly, link it — those prescriptions were chosen
         * from the catalogue, they just had no way to say so. Anything that
         * does not match stays free text, which is the correct outcome, not a
         * failure.
         */
        foreach (DB::table('medications')->get(['id', 'name']) as $medication) {
            DB::table('prescriptions')
                ->whereNull('medication_id')
                ->where('medication_name', $medication->name)
                ->update(['medication_id' => $medication->id]);
        }
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('medication_id');
        });
    }
};
