<?php

namespace Tests\Feature;

use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /*
     * The marketing site now READS ITS PRICES FROM THE DATABASE, so the home
     * page genuinely needs the central schema and the plans seeded. It did not
     * before — the figures were hardcoded — and this test began failing with
     * "no such table: plans" the moment they moved.
     *
     * Migrating rather than stubbing is the point: if a deploy ever reaches
     * production without the plans seeded, the pricing page is broken, and a
     * test that quietly tolerated an empty table would hide exactly that.
     */
    use RefreshDatabase;

    public function test_the_root_path_serves_the_marketing_home_page(): void
    {
        $this->seed(PlanSeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertSee('AfriChart', escape: false);
    }

    /**
     * The prices the visitor sees come from the `plans` table, not from code.
     *
     * Guards the single-source-of-truth property directly: change the seeder and
     * this follows; reintroduce a hardcoded array and it fails.
     */
    public function test_pricing_is_rendered_from_the_plans_table(): void
    {
        $this->seed(PlanSeeder::class);

        $response = $this->get('/pricing')->assertOk();

        // Confirmed commercial figures, 2026-08-25.
        $response->assertSee('45,000', escape: false);   // Starter monthly
        $response->assertSee('75,000', escape: false);   // Starter setup
        $response->assertSee('85,000', escape: false);   // Clinic monthly
        $response->assertSee('120,000', escape: false);  // Clinic setup
        $response->assertSee('65,000', escape: false);   // Group monthly, per site
        $response->assertSee('150,000', escape: false);  // Group setup

        // Group is per SITE — the suffix is part of the price, not decoration.
        $response->assertSee('/month per site', escape: false);

        // And the worked example is computed, not typed: 2 × ₦65,000.
        $response->assertSee('130,000', escape: false);
    }

    /**
     * A price changed in the DATABASE alone must reach the page — the property
     * that proves there is no second copy in code.
     */
    public function test_a_price_changed_in_the_database_reaches_the_page(): void
    {
        $this->seed(PlanSeeder::class);

        \App\Models\Plan::where('slug', 'starter')->update(['monthly_price' => 9_900_000]);

        $this->get('/pricing')
            ->assertOk()
            ->assertSee('99,000', escape: false)
            ->assertDontSee('&#8358;45,000', escape: false);
    }
}
