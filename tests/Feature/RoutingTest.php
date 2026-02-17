<?php

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

uses(TestCase::class);

it('registers web route', function (): void {
    $hasRoute = collect(Route::getRoutes())->contains(fn ($route): bool => $route->uri() === 'graph' &&
           in_array('GET', $route->methods()) &&
           $route->getName() === 'model-graph.index');

    expect($hasRoute)->toBeTrue();
});

it('registers api route', function (): void {
    $hasRoute = collect(Route::getRoutes())->contains(fn ($route): bool => $route->uri() === 'api/v1/graph/data' &&
           in_array('GET', $route->methods()) &&
           $route->getName() === 'model-graph.api.data');

    expect($hasRoute)->toBeTrue();
});

it('web route returns success', function (): void {
    $response = $this->get(route('model-graph.index'));
    $response->assertStatus(200);
    $response->assertViewIs('model-graph::graph');
});

it('api route returns success and json', function (): void {
    $response = $this->get(route('model-graph.api.data'));
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'models',
        'relationships',
    ]);
});
