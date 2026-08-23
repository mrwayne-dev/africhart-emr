<?php

namespace App\Providers;

use App\Models\PlatformAdmin;
use App\Models\Staff;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use OpenApi\Analysers\AttributeAnnotationFactory;
use OpenApi\Analysers\DocBlockAnnotationFactory;
use OpenApi\Analysers\ReflectionAnalyser;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /*
         * AfriChart uses doc-comment annotations (e.g. @OA\Get) rather than PHP 8
         * attributes, so l5-swagger needs a ReflectionAnalyser that reads BOTH.
         *
         * It cannot live in config/l5-swagger.php: an object literal there makes
         * `config:cache` fail outright, because it serialises with var_export()
         * and ReflectionAnalyser has no __set_state().
         *
         * Injecting it here works because Generator reads the analyser from the
         * config repository at generation time (Generator.php:245) — but it must
         * be SKIPPED while the config is being cached, since config:cache boots
         * the application first and would serialise whatever we just put there.
         * After caching, this provider re-injects it on every subsequent boot,
         * so the cached `null` is never what the generator actually sees.
         */
        $cachingConfig = $this->app->runningInConsole()
            && in_array($_SERVER['argv'][1] ?? '', ['config:cache', 'optimize'], true);

        if (! $cachingConfig) {
            config([
                'l5-swagger.defaults.scanOptions.analyser' => new ReflectionAnalyser([
                    new DocBlockAnnotationFactory,
                    new AttributeAnnotationFactory,
                ]),
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
         * Central migrations live in database/migrations/central (ARCHITECTURE
         * §4.3). Laravel's migrator globs a directory non-recursively, so once
         * the migrations moved into subdirectories the default path finds
         * nothing — this registers the central set explicitly.
         *
         * Tenant migrations are NOT registered here. stancl passes
         * `--path=database/migrations/tenant --realpath` when it migrates a
         * tenant, and Laravel's BaseCommand::getMigrationPaths() returns ONLY
         * the explicit --path when one is given. So `php artisan migrate` sees
         * central and nothing else, and `tenants:migrate` sees tenant and
         * nothing else. That mutual exclusion is the migration split.
         */
        $this->loadMigrationsFrom(database_path('migrations/central'));

        /*
         * Morph map — REQUIRED by the users → staff rename (ARCHITECTURE §8.3).
         *
         * personal_access_tokens.tokenable_type stores a fully-qualified class
         * NAME as a database value. Every token issued before the rename holds
         * the literal string 'App\Models\User'. Renaming the class does not
         * touch those rows: the token still exists, still matches on hash, and
         * then resolves to a class that no longer exists. Sanctum returns null
         * and the request 401s, with nothing in the log explaining why.
         *
         * The alias fixes both directions at once. 'staff' => Staff::class
         * means new tokens store the short alias instead of a class name, so
         * the next rename cannot break them either — which is the actual defect,
         * not the rename. Mapping the legacy FQCN alongside it keeps every
         * already-issued token resolving.
         *
         * A data migration rewriting tokenable_type would have fixed today and
         * left the same trap set for next time.
         */
        Relation::enforceMorphMap([
            'staff' => Staff::class,
            'App\\Models\\User' => Staff::class,   // tokens issued before the rename
            'platform_admin' => PlatformAdmin::class,
        ]);

        // Non-model abilities — admin-only features.
        Gate::define('view-audit-log', fn (Staff $user) => $user->isAdmin());
        Gate::define('export-data', fn (Staff $user) => $user->isAdmin());

        /*
         * Login throttle, 5 attempts per minute per email+IP.
         *
         * Same budget as the bare `throttle:5,1` it replaces, but the lockout
         * comes back as a redirect with a message the sign-in page can render,
         * instead of Laravel's raw 429 error page. Someone who has mistyped
         * their password five times is a real user having a bad morning, and
         * dumping them on "429 Too Many Requests" tells them nothing about what
         * to do next.
         *
         * Keyed on email AND IP so one person's lockout cannot lock out a
         * colleague at the same clinic behind the same NAT.
         */
        RateLimiter::for('login', function (Request $request) {
            $key = Str::transliterate(Str::lower((string) $request->input('email')).'|'.$request->ip());

            return Limit::perMinute(5)->by($key)->response(function (Request $request, array $headers) {
                $seconds = $headers['Retry-After'] ?? 60;

                return back()
                    ->withInput($request->only('email'))
                    ->withErrors([
                        'throttle' => 'Too many sign-in attempts. Try again in '
                            .($seconds > 60 ? ceil($seconds / 60).' minutes' : $seconds.' seconds').'.',
                    ]);
            });
        });
    }
}
