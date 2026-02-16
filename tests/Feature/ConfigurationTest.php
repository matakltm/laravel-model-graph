<?php

use Illuminate\Support\Facades\Artisan;
use Matakltm\LaravelModelGraph\Providers\LaravelModelGraphServiceProvider;
use Tests\TestCase;

uses(TestCase::class);

it('registers command when enabled', function () {
    // By default it is enabled in config
    expect(Artisan::all())->toHaveKey('model-graph:generate');
});

it('logic: isEnabled returns false when disabled', function () {
    $provider = new LaravelModelGraphServiceProvider(app());

    config(['model-graph.enabled' => false]);

    $reflection = new ReflectionClass($provider);
    $method = $reflection->getMethod('isEnabled');
    $method->setAccessible(true);

    expect($method->invoke($provider))->toBeFalse();
});

it('logic: respects environment settings', function () {
    $provider = new LaravelModelGraphServiceProvider(app());
    $reflection = new ReflectionClass($provider);
    $method = $reflection->getMethod('isEnabled');
    $method->setAccessible(true);

    // Test production allowed
    config([
        'model-graph.enabled' => true,
        'model-graph.environments' => ['production'],
        'model-graph.allow_production' => true,
    ]);
    // Mock environment
    app()->detectEnvironment(fn() => 'production');
    expect($method->invoke($provider))->toBeTrue();

    // Test production not allowed
    config([
        'model-graph.allow_production' => false,
    ]);
    expect($method->invoke($provider))->toBeFalse();

    // Reset to testing for further tests
    app()->detectEnvironment(fn() => 'testing');

    // Test environment mismatch
    config([
        'model-graph.environments' => ['local'],
    ]);
    expect($method->invoke($provider))->toBeFalse();

    // Test environment match
    config([
        'model-graph.environments' => ['testing'],
    ]);
    expect($method->invoke($provider))->toBeTrue();
});
