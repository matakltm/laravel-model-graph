<?php

use Matakltm\LaravelModelGraph\Services\SchemaInspectorService;

test('it can be instantiated', function (): void {
    $service = new SchemaInspectorService;
    expect($service)->toBeInstanceOf(SchemaInspectorService::class);
});

test('inspect method returns an array', function (): void {
    $service = new SchemaInspectorService;
    expect($service->inspect('App\Models\User'))->toBeArray();
});
