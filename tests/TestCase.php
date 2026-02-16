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
}
