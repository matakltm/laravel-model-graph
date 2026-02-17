<?php

use Tests\TestCase;

uses(TestCase::class);

test('web route returns 200', function (): void {
    $response = $this->get(route('model-graph.index'));
    $response->assertStatus(200);
    $response->assertViewIs('model-graph::graph');
});

test('api route returns 200 and JSON', function (): void {
    $response = $this->get(route('model-graph.api.data'));
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'version',
        'timestamp',
        'models',
        'relationships',
    ]);
});
