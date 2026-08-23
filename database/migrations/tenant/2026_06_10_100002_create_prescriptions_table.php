<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('consultation_id')->constrained()->onDelete('cascade');
            $table->foreignId('patient_id')->constrained()->onDelete('restrict');
            $table->foreignId('prescribed_by')->constrained('users')->onDelete('restrict');

            // Medication details
            $table->string('medication_name');
            $table->string('dosage');                   // e.g. "500mg"
            $table->string('frequency');                // e.g. "3 times daily"
            $table->string('duration');                 // e.g. "7 days"
            $table->string('route')->default('oral');   // oral, iv, im, ...
            $table->text('instructions')->nullable();
            $table->integer('quantity')->nullable();

            $table->timestamps();

            $table->index('consultation_id');
            $table->index('patient_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
