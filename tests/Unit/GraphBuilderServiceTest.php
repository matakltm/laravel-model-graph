<?php

use Matakltm\LaravelModelGraph\Services\GraphBuilderService;

test('it can be instantiated', function (): void {
    $service = new GraphBuilderService();
    expect($service)->toBeInstanceOf(GraphBuilderService::class);
});

test('generate method returns an array', function (): void {
    $service = new GraphBuilderService();
    expect($service->generate())->toBeArray();
});
