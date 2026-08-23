<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            /*
             * staff_id, not user_id. This FK used the bare constrained() form,
             * which infers its target table from the COLUMN NAME — so it
             * pointed at `users` without the word appearing anywhere, and a
             * grep for constrained('users') does not find it. Renaming the
             * column is what repoints it at `staff`.
             */
            $table->foreignId('staff_id')->nullable()->constrained('staff')->onDelete('set null');
            $table->string('user_name');                // Snapshot of name at time of action
            $table->string('action');                   // created, updated, deleted
            $table->string('model_type');               // App\Models\Patient, etc.
            $table->unsignedBigInteger('model_id');
            $table->string('description');              // Human-readable
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();

            $table->timestamp('created_at')->nullable();

            $table->index(['model_type', 'model_id']);
            $table->index('staff_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
