<?php

namespace Tests;

use Matakltm\LaravelModelGraph\Providers\LaravelModelGraphServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LaravelModelGraphServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.key', 'base64:Hupx3yAySlyf9YQ3t/iX1Yp2+H9tC7T9e4/v6H9tC7T=');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
