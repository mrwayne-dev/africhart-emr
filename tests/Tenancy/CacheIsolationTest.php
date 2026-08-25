<?php

namespace Tests\Tenancy;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * §6.3 — CACHE ISOLATION.
 *
 * > A cache key written in A's context is not readable in B's context.
 *
 * The distinction that matters here is HOW it is isolated. Under D1 the cache
 * driver is `database`, so each clinic's cache rows live in that clinic's own
 * `cache` table — isolation by LOCATION. The alternative, which stancl's
 * CacheTenancyBootstrapper provides and which we deliberately disabled, is
 * isolation by key PREFIX in one shared store: a forgotten prefix there is a
 * cross-tenant read, whereas a forgotten prefix here reaches a table that
 * simply does not contain the other clinic's data.
 *
 * So these tests assert both: the read returns null, AND the row is physically
 * in one database and absent from the other. The second is the one that would
 * catch a regression to prefix-based isolation, because prefixing also makes
 * the read return null.
 */
class CacheIsolationTest extends TenancyTestCase
{
    public function test_the_cache_bootstrapper_stays_disabled(): void
    {
        /*
         * A guard, not a preference. CacheTenancyBootstrapper swaps in a cache
         * manager that routes every call through ->tags(), which Laravel's
         * database store does not support — enabling it would throw on every
         * cache call inside a tenant. It would also move isolation from
         * location to prefix, quietly weakening what the tests below prove.
         */
        $this->assertNotContains(
            \Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
            config('tenancy.bootstrappers'),
            'CacheTenancyBootstrapper must stay disabled: it is incompatible with the database '
            .'cache store, and it replaces isolation-by-location with isolation-by-prefix.',
        );

        $this->assertSame('database', config('cache.default'));
    }

    public function test_a_cache_probe_written_in_one_clinic_is_invisible_in_the_other(): void
    {
        $a = $this->provisionClinic('alpha');
        $b = $this->provisionClinic('bravo');

        // Identical KEY in both clinics — the collision case. A prefix bug or a
        // shared store shows up here as one clinic reading the other's value.
        $key = 'shared-key-name';

        $this->inTenant($a, fn () => Cache::put($key, 'ALPHA-SECRET', 600));

        // THE LEAK ATTEMPT: same key, other clinic.
        $inB = $this->inTenant($b, fn () => Cache::get($key));

        $this->assertNull($inB, "LEAK: B read [{$inB}] from a key only A wrote.");

        // Control: A can still read its own value, so the null above is
        // isolation rather than the write having silently failed.
        $inA = $this->inTenant($a, fn () => Cache::get($key));
        $this->assertSame('ALPHA-SECRET', $inA, "A must still read its own cache entry.");
    }

    public function test_the_cache_row_is_physically_in_one_database_and_absent_from_the_other(): void
    {
        $a = $this->provisionClinic('alpha');
        $b = $this->provisionClinic('bravo');

        $this->inTenant($a, fn () => Cache::put('probe', 'ALPHA-SECRET', 600));

        $rowsInA = $this->inTenant($a, fn () => DB::table('cache')->count());
        $rowsInB = $this->inTenant($b, fn () => DB::table('cache')->count());

        $this->assertGreaterThan(0, $rowsInA, "A's cache table must hold the row it wrote.");
        $this->assertSame(0, $rowsInB, "B's cache table must be empty — isolation by LOCATION.");

        /*
         * And the value itself is nowhere in B's table. A prefix scheme would
         * put it there under a decorated key; a separate database cannot.
         */
        $bValues = $this->inTenant($b, fn () => DB::table('cache')->pluck('value')->implode('|'));
        $this->assertStringNotContainsString('ALPHA-SECRET', $bValues);
    }

    public function test_each_clinic_can_hold_a_different_value_under_the_same_key(): void
    {
        $a = $this->provisionClinic('alpha');
        $b = $this->provisionClinic('bravo');

        $this->inTenant($a, fn () => Cache::put('consultation-fee', 'ALPHA-5000', 600));
        $this->inTenant($b, fn () => Cache::put('consultation-fee', 'BRAVO-9000', 600));

        // Neither write clobbers the other — which a shared store would do.
        $this->assertSame('ALPHA-5000', $this->inTenant($a, fn () => Cache::get('consultation-fee')));
        $this->assertSame('BRAVO-9000', $this->inTenant($b, fn () => Cache::get('consultation-fee')));
    }

    public function test_clearing_one_clinics_cache_leaves_the_others_intact(): void
    {
        $a = $this->provisionClinic('alpha');
        $b = $this->provisionClinic('bravo');

        $this->inTenant($a, fn () => Cache::put('probe', 'ALPHA-SECRET', 600));
        $this->inTenant($b, fn () => Cache::put('probe', 'BRAVO-SECRET', 600));

        // A destructive operation is the sharpest test of a shared store: if the
        // cache were shared, flushing A would take B's data with it.
        $this->inTenant($a, fn () => Cache::flush());

        $this->assertNull($this->inTenant($a, fn () => Cache::get('probe')));
        $this->assertSame(
            'BRAVO-SECRET',
            $this->inTenant($b, fn () => Cache::get('probe')),
            "LEAK: flushing A's cache destroyed B's data.",
        );
    }

    public function test_a_central_cache_entry_is_not_visible_inside_a_tenant(): void
    {
        $a = $this->provisionClinic('alpha');

        Cache::put('platform-probe', 'CENTRAL-SECRET', 600);

        $inTenant = $this->inTenant($a, fn () => Cache::get('platform-probe'));

        $this->assertNull($inTenant, "LEAK: a tenant read the central cache entry [{$inTenant}].");
        $this->assertSame('CENTRAL-SECRET', Cache::get('platform-probe'), 'Central must keep its own entry.');
    }
}
