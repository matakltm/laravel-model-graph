<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Providers;

use Illuminate\Support\ServiceProvider;
use Matakltm\LaravelModelGraph\Commands\GenerateGraphCommand;

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
            __DIR__ . '/../../config/model-graph.php',
            'model-graph'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateGraphCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__ . '/../../config/model-graph.php' => config_path('model-graph.php'),
        ], 'model-graph-config');

        $this->publishes([
            __DIR__ . '/../../resources/dist' => public_path('vendor/model-graph'),
        ], 'model-graph-assets');

        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'model-graph');
    }
}
