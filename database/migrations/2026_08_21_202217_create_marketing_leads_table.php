<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marketing lead capture (Book a Demo / Get Started).
 *
 * A CENTRAL table: it belongs to the platform, not to any clinic, so when
 * multi-tenancy lands (Phase 2 A1) this migration stays in the central set
 * and is never run against a tenant database.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_leads', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);              // 'demo' | 'signup'
            $table->string('clinic_name');
            $table->string('contact_name');
            $table->string('email');
            $table->string('phone', 30);             // WhatsApp-reachable
            $table->string('city')->nullable();
            $table->unsignedSmallInteger('doctors')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_leads');
    }
};
