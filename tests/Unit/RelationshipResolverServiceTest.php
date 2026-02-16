<?php

use Matakltm\LaravelModelGraph\Services\RelationshipResolverService;
use Tests\TestCase;

uses(TestCase::class);

test('it can be instantiated', function (): void {
    $service = new RelationshipResolverService;
    expect($service)->toBeInstanceOf(RelationshipResolverService::class);
});

test('resolve method returns an array', function (): void {
    $service = new RelationshipResolverService;
    expect($service->resolve('App\Models\User'))->toBeArray();
});
