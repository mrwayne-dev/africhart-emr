<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Subscription tiers — CENTRAL (ARCHITECTURE.md §4.1).
 *
 * Reference data, not clinic data: the tiers are the same for everyone, so they
 * belong beside the registry rather than being copied into every tenant.
 *
 * `features` is the map B2 will gate against. It lives here rather than in code
 * so a tier's contents can change without a deploy, and so what a clinic is
 * entitled to is a queryable fact rather than a constant somebody has to
 * remember to keep in step with the pricing page.
 *
 * Runs before create_clinics, which takes a foreign key on `slug`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 20)->unique();          // starter | clinic | group
            $table->string('name');
            $table->unsignedInteger('monthly_price');       // kobo — integers, never floats, for money
            $table->unsignedInteger('setup_fee');           // kobo
            $table->unsignedSmallInteger('max_doctors')->nullable();  // null = unmetered
            $table->unsignedSmallInteger('max_sites')->nullable();
            $table->json('features');                       // B2 gating map
            $table->boolean('is_active')->default(true);    // retire a tier without deleting it
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
