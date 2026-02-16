<?php

use Matakltm\LaravelModelGraph\Services\GraphBuilderService;
use Matakltm\LaravelModelGraph\Services\ModelScannerService;
use Matakltm\LaravelModelGraph\Services\RelationshipResolverService;
use Matakltm\LaravelModelGraph\Services\SchemaInspectorService;

test('it can be instantiated', function (): void {
    $service = new GraphBuilderService(
        new ModelScannerService,
        new RelationshipResolverService,
        new SchemaInspectorService
    );
    expect($service)->toBeInstanceOf(GraphBuilderService::class);
});

test('generate method returns an array', function (): void {
    $service = new GraphBuilderService(
        new ModelScannerService,
        new RelationshipResolverService,
        new SchemaInspectorService
    );
    expect($service->generate())->toBeArray();
});
