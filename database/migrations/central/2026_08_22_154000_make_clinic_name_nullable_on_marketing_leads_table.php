<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Contact form accepts an enquiry with no clinic name.
 *
 * Demo and Sign-Up are both from a clinic, so the column was created NOT NULL.
 * A general enquiry may come from someone who does not run one yet — a doctor
 * weighing up going private, a practice manager scoping options — and demanding
 * a clinic name at the first field turns exactly those people away.
 *
 * Validation still REQUIRES it on demo and signup (see LeadRequest); only the
 * storage constraint relaxes.
 *
 * CENTRAL table — see the create migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_leads', function (Blueprint $table) {
            $table->string('clinic_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('marketing_leads', function (Blueprint $table) {
            $table->string('clinic_name')->nullable(false)->change();
        });
    }
};
