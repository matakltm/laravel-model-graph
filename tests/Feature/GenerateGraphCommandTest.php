<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Matakltm\LaravelModelGraph\Commands\GenerateGraphCommand;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->exportPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, storage_path('app/laravel-model-graph-test.json'));
    Config::set('model-graph.export_path', $this->exportPath);

    if (File::exists($this->exportPath)) {
        File::delete($this->exportPath);
    }
});

afterEach(function (): void {
    if (File::exists($this->exportPath)) {
        File::delete($this->exportPath);
    }
});

test('generate graph command exists', function (): void {
    expect(class_exists(GenerateGraphCommand::class))->toBeTrue();
});

test('command can be called and generates file', function (): void {
    $this->artisan('model-graph:generate')
        ->expectsOutput('Generating model graph...')
        ->expectsOutput('Model graph successfully generated and saved to: '.$this->exportPath)
        ->assertExitCode(0);

    expect(File::exists($this->exportPath))->toBeTrue();
});

test('command respects dry-run option', function (): void {
    $this->artisan('model-graph:generate', ['--dry-run' => true])
        ->expectsOutput('Generating model graph...')
        ->expectsOutput('Dry run: Graph data generated but not saved.')
        ->assertExitCode(0);

    expect(File::exists($this->exportPath))->toBeFalse();
});

test('command respects pretty option', function (): void {
    $this->artisan('model-graph:generate', ['--pretty' => true, '--dry-run' => true])
        ->expectsOutput('Generating model graph...')
        ->assertExitCode(0);
});

test('command asks for confirmation if file exists and force is not set', function (): void {
    File::put($this->exportPath, 'old content');

    $this->artisan('model-graph:generate')
        ->expectsConfirmation(sprintf('File [%s] already exists. Overwrite?', $this->exportPath), 'no')
        ->expectsOutput('Generation cancelled.')
        ->assertExitCode(0);

    expect(File::get($this->exportPath))->toBe('old content');
});

test('command overwrites file if force is set', function (): void {
    File::put($this->exportPath, 'old content');

    $this->artisan('model-graph:generate', ['--force' => true])
        ->expectsOutput('Generating model graph...')
        ->assertExitCode(0);

    expect(File::get($this->exportPath))->not->toBe('old content');
});

test('command respects output option', function (): void {
    $customPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, storage_path('app/custom-graph.json'));
    if (File::exists($customPath)) {
        File::delete($customPath);
    }

    $this->artisan('model-graph:generate', ['--output' => $customPath])
        ->expectsOutput('Generating model graph...')
        ->expectsOutput('Model graph successfully generated and saved to: '.$customPath)
        ->assertExitCode(0);

    expect(File::exists($customPath))->toBeTrue();
    File::delete($customPath);
});
