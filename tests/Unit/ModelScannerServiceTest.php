<?php

use Matakltm\LaravelModelGraph\Services\ModelScannerService;
use Tests\TestCase;

uses(TestCase::class);

test('it can be instantiated', function (): void {
    $service = new ModelScannerService;
    expect($service)->toBeInstanceOf(ModelScannerService::class);
});

test('scan method returns an array', function (): void {
    $service = new ModelScannerService;
    expect($service->scan())->toBeArray();
});
