<?php

declare(strict_types=1);

namespace App\Tenancy;

use App\Models\Clinic;
use Stancl\Tenancy\Contracts\Tenant;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedOnDomainException;
use Stancl\Tenancy\Resolvers\Contracts\CachedTenantResolver;

/**
 * Resolves a clinic from its subdomain label.
 *
 * stancl ships DomainTenantResolver, which joins a `domains` table. We do not
 * have one: ARCHITECTURE.md §3.2 makes `clinics.subdomain` the single source of
 * truth for where a clinic lives, and step 1 dropped the domains migration
 * accordingly. Two tables describing the same fact is one more than can be kept
 * in step, and the failure mode is a clinic reachable at an address the
 * registry does not know about.
 *
 * Caching is inherited but left OFF (see $shouldCache). Every request would
 * otherwise hit the central database for a lookup that rarely changes — but
 * turning it on has a sharp edge worth stating rather than discovering: the
 * cache lives in whatever store is active, and under D1 that is the DATABASE
 * store. Enabling it before the tenant connection is swapped means the lookup
 * is cached centrally, which is correct; enabling it carelessly later, after
 * the swap, would cache one clinic's resolution inside another clinic's
 * database. Leave it off until there is a measured reason, and treat switching
 * it on as a change to the isolation story.
 */
class SubdomainTenantResolver extends CachedTenantResolver
{
    /** @var bool */
    public static $shouldCache = false;

    /** @var int */
    public static $cacheTTL = 3600;

    /** @var string|null */
    public static $cacheStore = null;

    public function resolveWithoutCache(...$args): Tenant
    {
        $subdomain = (string) ($args[0] ?? '');

        /** @var Clinic|null $clinic */
        $clinic = Clinic::query()->where('subdomain', $subdomain)->first();

        if ($clinic) {
            return $clinic;
        }

        /*
         * The concrete OnDomain exception, not the abstract base — it implements
         * the TenantCouldNotBeIdentifiedException CONTRACT that
         * IdentificationMiddleware catches, so the friendly page still fires.
         */
        throw new TenantCouldNotBeIdentifiedOnDomainException($subdomain);
    }

    /**
     * The inverse: which subdomain(s) identify this tenant. Used to invalidate
     * the resolver cache when a clinic changes.
     */
    public function getArgsForTenant(Tenant $tenant): array
    {
        return [
            [$tenant->subdomain],
        ];
    }
}
