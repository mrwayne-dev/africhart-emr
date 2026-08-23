<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('patient_id')->constrained()->onDelete('restrict');
            $table->foreignId('doctor_id')->constrained('users')->onDelete('restrict');

            // Clinical data
            $table->text('chief_complaint');
            $table->text('clinical_notes');
            $table->text('diagnosis')->nullable();
            $table->text('plan')->nullable();
            $table->enum('status', ['in_progress', 'completed', 'follow_up'])
                ->default('in_progress');

            // Vitals (captured by nurse or doctor)
            $table->decimal('temperature', 4, 1)->nullable();   // °C
            $table->string('blood_pressure', 10)->nullable();   // e.g. "120/80"
            $table->integer('pulse_rate')->nullable();          // bpm
            $table->decimal('weight', 5, 1)->nullable();        // kg
            $table->decimal('height', 5, 1)->nullable();        // cm
            $table->text('vitals_notes')->nullable();

            $table->string('consultation_id', 25)->unique();    // ACH-C-YYYYMMDD-XXXX
            $table->timestamps();

            $table->index('patient_id');
            $table->index('doctor_id');
            $table->index('status');
            $table->index('consultation_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
