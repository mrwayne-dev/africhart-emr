<?php

declare(strict_types=1);

namespace App\Tenancy;

use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

/**
 * Makes the `database` cache store follow the tenant connection.
 *
 * ── The bug this fixes, and it was mine ────────────────────────────────────
 *
 * D1 says cache isolation is structural: each clinic's cache rows live in that
 * clinic's own database. When CacheTenancyBootstrapper was disabled in step 1 —
 * correctly, since it routes every call through ->tags() which the database
 * store cannot do — the reasoning recorded was that DatabaseTenancyBootstrapper
 * already swaps the connection, so the cache table being read is the tenant's
 * own.
 *
 * That was wrong. Laravel's CacheManager resolves a store ONCE and the
 * DatabaseStore holds a live Connection OBJECT, not a connection name. Swapping
 * the default connection afterwards does not touch it. Every cache read and
 * write in every tenant went to africhart_central.
 *
 * The §6.3 acceptance tests caught it exactly as intended: a probe written in
 * clinic A was readable in clinic B, A's own cache table had zero rows, and
 * flushing A destroyed B's data.
 *
 * ── The fix ────────────────────────────────────────────────────────────────
 *
 * Forget the resolved store whenever tenancy starts or ends, so the next cache
 * call re-resolves against whatever the default connection now is. No tags, no
 * prefixes — the row simply lands in the tenant's own `cache` table, which is
 * the isolation D1 asked for.
 */
class DatabaseCacheTenancyBootstrapper implements TenancyBootstrapper
{
    public function __construct(protected Application $app) {}

    public function bootstrap(Tenant $tenant): void
    {
        $this->forgetResolvedStores();
    }

    public function revert(): void
    {
        $this->forgetResolvedStores();
    }

    /**
     * Drop every resolved cache store AND the facade's own cached instance.
     *
     * Both are needed: forgetDriver() clears the manager, but the Cache facade
     * keeps its own resolved instance and would go on using the stale store.
     */
    protected function forgetResolvedStores(): void
    {
        $manager = $this->app->make('cache');

        if ($manager instanceof CacheManager) {
            foreach (array_keys($this->app['config']->get('cache.stores', [])) as $store) {
                $manager->forgetDriver($store);
            }
        }

        Cache::clearResolvedInstances();
    }
}
