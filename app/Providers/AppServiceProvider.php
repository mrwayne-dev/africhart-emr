<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
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
        // Non-model abilities — admin-only features.
        Gate::define('view-audit-log', fn (User $user) => $user->isAdmin());
        Gate::define('export-data', fn (User $user) => $user->isAdmin());

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
