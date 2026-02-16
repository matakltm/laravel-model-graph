<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Matakltm\LaravelModelGraph\Commands\GenerateGraphCommand;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->storagePath = storage_path('app/laravel-model-graph-test.json');
    Config::set('model-graph.storage_path', $this->storagePath);

    if (File::exists($this->storagePath)) {
        File::delete($this->storagePath);
    }
});

afterEach(function (): void {
    if (File::exists($this->storagePath)) {
        File::delete($this->storagePath);
    }
});

test('generate graph command exists', function (): void {
    expect(class_exists(GenerateGraphCommand::class))->toBeTrue();
});

test('command can be called and generates file', function (): void {
    $this->artisan('model-graph:generate')
        ->expectsOutput('Generating model graph...')
        ->expectsOutput('Model graph successfully generated and saved to: '.$this->storagePath)
        ->assertExitCode(0);

    expect(File::exists($this->storagePath))->toBeTrue();
});

test('command respects dry-run option', function (): void {
    $this->artisan('model-graph:generate --dry-run')
        ->expectsOutput('Generating model graph...')
        ->expectsOutput('Dry run: Graph data generated but not saved.')
        ->assertExitCode(0);

    expect(File::exists($this->storagePath))->toBeFalse();
});

test('command respects pretty option', function (): void {
    $this->artisan('model-graph:generate --pretty --dry-run')
        ->expectsOutput('Generating model graph...')
        ->assertExitCode(0);
});

test('command asks for confirmation if file exists and force is not set', function (): void {
    File::put($this->storagePath, 'old content');

    $this->artisan('model-graph:generate')
        ->expectsConfirmation(sprintf('File [%s] already exists. Overwrite?', $this->storagePath), 'no')
        ->expectsOutput('Generation cancelled.')
        ->assertExitCode(0);

    expect(File::get($this->storagePath))->toBe('old content');
});

test('command overwrites file if force is set', function (): void {
    File::put($this->storagePath, 'old content');

    $this->artisan('model-graph:generate --force')
        ->expectsOutput('Generating model graph...')
        ->assertExitCode(0);

    expect(File::get($this->storagePath))->not->toBe('old content');
});
