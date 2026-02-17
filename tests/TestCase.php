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
        $app['config']->set('app.key', 'base64:uK6M8l/Fh4Ua9y/X6A8j6uK6M8l/Fh4Ua9y/X6A8j6A=');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
