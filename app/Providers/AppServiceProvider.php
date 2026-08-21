<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
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
    }
}
