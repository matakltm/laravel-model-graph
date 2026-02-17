<?php

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    Config::set('model-graph.enabled', true);
    Config::set('model-graph.environments', ['testing']);
});

test('it can access the graph page', function (): void {
    $response = $this->get('/graph');

    $response->assertStatus(200);
    $response->assertViewIs('model-graph::graph');
});

test('it can access the api data', function (): void {
    $response = $this->getJson('/api/v1/graph/data');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'models',
        'relationships',
    ]);
});

test('it can access assets', function (): void {
    $response = $this->get('/graph/assets/app.js');

    $response->assertStatus(200);
    // Note: Mime type might vary depending on environment, but it should be JS
    $this->assertStringContainsString('javascript', $response->headers->get('Content-Type'));
    $this->assertStringContainsString('public', $response->headers->get('Cache-Control'));
    $this->assertStringContainsString('max-age=31536000', $response->headers->get('Cache-Control'));
});

test('it returns 404 for missing assets', function (): void {
    $response = $this->get('/graph/assets/non-existent.js');

    $response->assertStatus(404);
});

test('it prevents path traversal', function (): void {
    // Try to access the .env file (if it exists) or just something outside resources/dist
    $response = $this->get('/graph/assets/../composer.json');

    $response->assertStatus(404);
});
