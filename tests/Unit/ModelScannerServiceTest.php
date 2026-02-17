<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Matakltm\LaravelModelGraph\Events\ModelDiscovered;
use Matakltm\LaravelModelGraph\Services\ModelScannerService;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->testModelsPath = __DIR__.'/../tmp/Models';
    if (! File::exists($this->testModelsPath)) {
        File::makeDirectory($this->testModelsPath, 0755, true);
    }

    // Create some fake models
    File::put($this->testModelsPath.'/User.php', "<?php namespace Tests\\tmp\\Models; class User extends \Illuminate\Database\Eloquent\Model {}");
    File::put($this->testModelsPath.'/Post.php', "<?php namespace Tests\\tmp\\Models; class Post extends \Illuminate\Database\Eloquent\Model {}");

    Config::set('model-graph.scan.models_path', $this->testModelsPath);

    // Clear cache
    Cache::forget('laravel-model-graph-models');
});

afterEach(function () {
    if (File::exists(__DIR__.'/../tmp')) {
        File::deleteDirectory(__DIR__.'/../tmp');
    }
});

test('it respects exclude filter', function (): void {
    Config::set('model-graph.scan.exclude', ['Tests\\tmp\\Models\\Post']);

    $service = new ModelScannerService;
    $models = $service->scan();

    expect($models)->toContain('Tests\\tmp\\Models\\User');
    expect($models)->not->toContain('Tests\\tmp\\Models\\Post');
});

test('it respects include_only filter', function (): void {
    Config::set('model-graph.scan.include_only', ['Tests\\tmp\\Models\\User']);

    $service = new ModelScannerService;
    $models = $service->scan();

    expect($models)->toContain('Tests\\tmp\\Models\\User');
    expect($models)->not->toContain('Tests\\tmp\\Models\\Post');
});

test('it caches results and still fires events', function (): void {
    $service = new ModelScannerService;

    // First scan
    $models = $service->scan();
    expect(Cache::has('laravel-model-graph-models'))->toBeTrue();
    expect(Cache::get('laravel-model-graph-models'))->toBe($models);

    // Second scan (from cache)
    Event::fake();
    $modelsCached = $service->scan();

    expect($modelsCached)->toBe($models);
    Event::assertDispatched(ModelDiscovered::class, 2);
    Event::assertDispatched(ModelDiscovered::class, fn (ModelDiscovered $event): bool => $event->modelClass === 'Tests\\tmp\\Models\\User');
});

test('it fires ModelDiscovered events', function (): void {
    Event::fake();

    $service = new ModelScannerService;
    $service->scan();

    Event::assertDispatched(ModelDiscovered::class, fn (ModelDiscovered $event): bool => $event->modelClass === 'Tests\\tmp\\Models\\User');
    Event::assertDispatched(ModelDiscovered::class, fn (ModelDiscovered $event): bool => $event->modelClass === 'Tests\\tmp\\Models\\Post');
});

test('it handles global namespace models', function (): void {
    $tempFile = $this->testModelsPath.'/GlobalModel.php';
    File::put($tempFile, "<?php class GlobalModel extends \Illuminate\Database\Eloquent\Model {}");

    $service = new class extends ModelScannerService
    {
        public function publicGetClassFromFile(string $path): ?string
        {
            return $this->getClassFromFile($path);
        }
    };

    $class = $service->publicGetClassFromFile($tempFile);
    expect($class)->toBe('GlobalModel');
});
