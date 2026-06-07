<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();

            // Core identity
            $table->string('full_name');
            $table->date('date_of_birth');
            $table->string('phone', 20);

            // Medical basics
            $table->string('blood_group', 5);   // A+, A-, B+, B-, AB+, AB-, O+, O-
            $table->text('allergies')->nullable();

            // System fields
            $table->string('patient_id', 20)->unique(); // Auto-generated: ACH-YYYYMMDD-XXXX
            $table->foreignId('registered_by')
                ->constrained('users')
                ->onDelete('restrict');             // Never delete a user who registered patients

            $table->timestamps();

            // Indexes for search performance
            $table->index('full_name');
            $table->index('phone');
            $table->index('patient_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
