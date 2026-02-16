<?php

namespace Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Matakltm\LaravelModelGraph\Providers\LaravelModelGraphServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LaravelModelGraphServiceProvider::class,
        ];
    }
}
