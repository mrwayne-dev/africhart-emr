<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Tenancy\SubdomainTenantResolver;
use Closure;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\TenantCouldNotBeIdentifiedException;
use Stancl\Tenancy\Middleware\IdentificationMiddleware;
use Stancl\Tenancy\Tenancy;

/**
 * Identifies the clinic from the request's subdomain and initialises tenancy.
 *
 * Ours rather than stancl's InitializeTenancyBySubdomain, because that class
 * type-hints DomainTenantResolver in its constructor and we resolve against
 * clinics.subdomain instead (see SubdomainTenantResolver). Extending it would
 * have meant binding our resolver over theirs in the container — action at a
 * distance, to avoid writing forty lines.
 *
 * On an unrecognised subdomain this renders a friendly "no such clinic" page
 * rather than throwing (§3.2). The nginx `444` stays correct for junk
 * hostnames pointed at the box; it is the wrong answer for a receptionist who
 * mistyped their own clinic's name.
 */
class InitializeTenancyBySubdomain extends IdentificationMiddleware
{
    /*
     * Plain assignment, NOT promoted+typed properties.
     *
     * IdentificationMiddleware declares `protected $tenancy` and
     * `protected $resolver` untyped, and PHP forbids a subclass from adding a
     * type to an inherited property — "Type of ...::$tenancy must not be
     * defined". Promotion would redeclare them, so it fatals at class load.
     */
    public function __construct(Tenancy $tenancy, SubdomainTenantResolver $resolver)
    {
        $this->tenancy = $tenancy;
        $this->resolver = $resolver;
    }

    public function handle($request, Closure $next)
    {
        $subdomain = $this->subdomainFrom($request->getHost());

        // No subdomain at all: a central domain, an IP, or a hostname that is
        // not ours. Tenant routes are also wrapped in
        // PreventAccessFromCentralDomains, which is what actually rejects it —
        // this only declines to guess a tenant.
        if ($subdomain === null) {
            return $this->noSuchClinic($request, null);
        }

        try {
            $this->tenancy->initialize($this->resolver->resolve($subdomain));
        } catch (TenantCouldNotBeIdentifiedException) {
            return $this->noSuchClinic($request, $subdomain);
        }

        /*
         * Discard the {clinic} route parameter.
         *
         * routes/tenant.php scopes the group to '{clinic}.<root_domain>' so
         * tenant URIs cannot collide with central ones. Laravel passes domain
         * parameters to the route action as the FIRST argument, which would
         * mean every controller method in the EMR growing a $clinic it never
         * asked for. The tenant is already resolved and available via tenant();
         * the placeholder has done its job by the time we get here.
         */
        $request->route()?->forgetParameter('clinic');

        /*
         * Every tenant route now carries a {clinic} domain parameter, so
         * route('dashboard') would demand it be passed at each of the hundreds
         * of call sites in the EMR's views. Registering it as a URL default
         * means generation just works inside tenant context and nothing else
         * had to change.
         *
         * Only set here, never centrally — a central page generating a tenant
         * URL SHOULD fail loudly rather than silently produce a link to the
         * wrong clinic.
         */
        URL::defaults(['clinic' => tenant('subdomain')]);

        return $next($request);
    }

    /**
     * The label in front of a central domain, or null if this host is not a
     * subdomain of one.
     */
    protected function subdomainFrom(string $hostname): ?string
    {
        $central = (array) config('tenancy.central_domains');

        // An exact central domain is not a tenant.
        if (in_array($hostname, $central, true)) {
            return null;
        }

        $parts = explode('.', $hostname);

        // localhost, or a bare IP — neither carries a subdomain.
        if (count($parts) === 1) {
            return null;
        }
        if (count(array_filter($parts, 'is_numeric')) === count($parts)) {
            return null;
        }

        /*
         * Must sit UNDER one of our central domains. Str::endsWith alone is not
         * enough: "notafrichartemr.com" ends with "africhartemr.com" as a
         * string. Requiring the dot means only a real subdomain matches, and a
         * lookalike domain parked at this IP cannot impersonate a clinic.
         */
        foreach ($central as $domain) {
            if (Str::endsWith($hostname, '.'.$domain)) {
                return $parts[0];
            }
        }

        return null;
    }

    protected function noSuchClinic($request, ?string $subdomain)
    {
        $payload = [
            'subdomain' => $subdomain,
            'rootDomain' => config('tenancy.root_domain'),
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'No clinic exists at this address.',
            ], 404);
        }

        return response()->view('errors.no-such-clinic', $payload, 404);
    }
}
