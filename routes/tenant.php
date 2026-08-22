<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Routes served on a clinic's own subdomain, inside tenant context.
|
| Deliberately empty until A1 step 4 (subdomain identification). The version
| stancl publishes declares `GET /` inside the tenancy middleware group, which
| would race the marketing site's home route — the first registration to match
| wins, and which one that is depends on provider boot order. An empty file
| makes registering TenancyServiceProvider inert for routing, so this step
| changes configuration only.
|
| When the clinic app moves in here it takes the middleware stack:
|   'web', InitializeTenancyBySubdomain, PreventAccessFromCentralDomains
|
*/
