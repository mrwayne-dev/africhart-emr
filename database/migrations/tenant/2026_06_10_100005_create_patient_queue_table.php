<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_queue', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('checked_in_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('assigned_doctor_id')->nullable()->constrained('users')->onDelete('set null');

            $table->enum('status', ['waiting', 'in_consultation', 'completed', 'cancelled'])
                ->default('waiting');
            $table->integer('queue_number');            // Daily sequential: 1, 2, 3...
            $table->text('reason')->nullable();
            $table->timestamp('checked_in_at');
            $table->timestamp('seen_at')->nullable();   // When doctor started consultation
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('checked_in_at');
            $table->index('assigned_doctor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_queue');
    }
};
