<?php

use Matakltm\LaravelModelGraph\Commands\GenerateGraphCommand;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

uses(TestCase::class);

test('generate graph command exists', function (): void {
    expect(class_exists(GenerateGraphCommand::class))->toBeTrue();
});

test('command can be called', function (): void {
    $this->artisan('model-graph:generate')
        ->assertExitCode(0)
        ->expectsOutput('Generating model graph...');
});
