<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Makes `plans` the SINGLE SOURCE OF TRUTH for pricing — CENTRAL.
 *
 * The prices were living in four places: this table, a hardcoded array in
 * MarketingController::tiers(), and twice more in signup.blade.php. Two sources
 * of truth for what a clinic pays is how somebody ends up billed something
 * other than the advertised figure; four is just waiting for it.
 *
 * Moving the numbers alone would not have been enough — /pricing also needs the
 * blurb, the CTA label, which tier is featured and the bullet list beside each
 * price. Leaving those in code would mean the page still could not be rendered
 * from the table, and the array would survive. So the presentation travels with
 * the price.
 *
 * `price_basis` is the one that carries real commercial meaning: Group is
 * priced per-SITE, not a flat fee, and a column that cannot express that would force
 * the distinction back into the view as a special case on the slug.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // 'flat' — one monthly price for the clinic.
            // 'per_site' — the monthly price is charged for EACH location.
            $table->string('price_basis', 20)->default('flat')->after('setup_fee');

            $table->string('blurb')->nullable()->after('name');
            $table->string('cta_label', 40)->default('Start free trial')->after('blurb');
            $table->boolean('is_featured')->default(false)->after('is_active');

            // The marketing bullet list: [['label' => …, 'included' => bool], …].
            // Deliberately separate from `features`, which is the machine-readable
            // gating map B2 enforces against. One is sales copy, the other is
            // entitlement — conflating them would let a copy edit change what a
            // clinic is allowed to do.
            $table->json('highlights')->nullable()->after('features');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['price_basis', 'blurb', 'cta_label', 'is_featured', 'highlights']);
        });
    }
};
