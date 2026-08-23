<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_queue', function (Blueprint $table) {
            // Vitals taken by the nurse while the patient is still waiting; the
            // consultation absorbs these on start.
            $table->decimal('temperature', 4, 1)->nullable()->after('reason');
            $table->string('blood_pressure', 10)->nullable()->after('temperature');
            $table->integer('pulse_rate')->nullable()->after('blood_pressure');
            $table->decimal('weight', 5, 1)->nullable()->after('pulse_rate');
            $table->decimal('height', 5, 1)->nullable()->after('weight');
            $table->text('vitals_notes')->nullable()->after('height');
            $table->foreignId('vitals_recorded_by')->nullable()->after('vitals_notes')
                ->constrained('users')->onDelete('set null');
            $table->timestamp('vitals_recorded_at')->nullable()->after('vitals_recorded_by');
        });
    }

    public function down(): void
    {
        Schema::table('patient_queue', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vitals_recorded_by');
            $table->dropColumn([
                'temperature',
                'blood_pressure',
                'pulse_rate',
                'weight',
                'height',
                'vitals_notes',
                'vitals_recorded_at',
            ]);
        });
    }
};
