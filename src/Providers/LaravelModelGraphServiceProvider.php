<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Providers;

use Illuminate\Support\ServiceProvider;
use Matakltm\LaravelModelGraph\Commands\GenerateGraphCommand;
use Matakltm\LaravelModelGraph\Services\GraphBuilderService;
use Matakltm\LaravelModelGraph\Services\ModelScannerService;
use Matakltm\LaravelModelGraph\Services\RelationshipResolverService;
use Matakltm\LaravelModelGraph\Services\SchemaInspectorService;

/**
 * Class LaravelModelGraphServiceProvider
 *
 * The service provider for the Laravel Model Graph package.
 */
class LaravelModelGraphServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/model-graph.php',
            'model-graph'
        );

        $this->app->singleton(ModelScannerService::class, fn (): \Matakltm\LaravelModelGraph\Services\ModelScannerService => new ModelScannerService);
        $this->app->singleton(RelationshipResolverService::class, fn (): \Matakltm\LaravelModelGraph\Services\RelationshipResolverService => new RelationshipResolverService);
        $this->app->singleton(SchemaInspectorService::class, fn (): \Matakltm\LaravelModelGraph\Services\SchemaInspectorService => new SchemaInspectorService);

        $this->app->singleton(GraphBuilderService::class, function (\Illuminate\Contracts\Foundation\Application $app): \Matakltm\LaravelModelGraph\Services\GraphBuilderService {
            /** @var ModelScannerService $scanner */
            $scanner = $app->make(ModelScannerService::class);
            /** @var RelationshipResolverService $resolver */
            $resolver = $app->make(RelationshipResolverService::class);
            /** @var SchemaInspectorService $inspector */
            $inspector = $app->make(SchemaInspectorService::class);

            return new GraphBuilderService($scanner, $resolver, $inspector);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/model-graph.php' => config_path('model-graph.php'),
        ], 'model-graph-config');

        $this->publishes([
            __DIR__.'/../../resources/dist' => public_path('vendor/model-graph'),
        ], 'model-graph-assets');

        if (! $this->isEnabled()) {
            return;
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateGraphCommand::class,
            ]);
        }

        if (config('model-graph.spa_enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
            $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        }

        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'model-graph');
    }

    /**
     * Check if the package is enabled.
     */
    protected function isEnabled(): bool
    {
        if (! (bool) config('model-graph.enabled', true)) {
            return false;
        }

        if ($this->app->environment('production') && ! (bool) config('model-graph.allow_production', false)) {
            return false;
        }

        /** @var array<int, string>|string $environments */
        $environments = config('model-graph.environments', ['local', 'testing']);

        return empty($environments) || $this->app->environment($environments);
    }
}
