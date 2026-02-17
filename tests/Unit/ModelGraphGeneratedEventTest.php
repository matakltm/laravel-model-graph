<?php

use Illuminate\Support\Facades\Event;
use Matakltm\LaravelModelGraph\Events\ModelGraphGenerated;
use Matakltm\LaravelModelGraph\Services\GraphBuilderService;
use Tests\TestCase;

uses(TestCase::class);

test('it dispatches ModelGraphGenerated event', function (): void {
    Event::fake();

    $service = app(GraphBuilderService::class);
    $service->generate([]);

    Event::assertDispatched(ModelGraphGenerated::class);
});
