<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Matakltm\LaravelModelGraph\Events\ModelDiscovered;
use Matakltm\LaravelModelGraph\Services\ModelScannerService;
use Tests\TestCase;

uses(TestCase::class);

test('it can be instantiated', function (): void {
    $service = new ModelScannerService;
    expect($service)->toBeInstanceOf(ModelScannerService::class);
});

test('it scans models', function (): void {
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
    Event::assertDispatched(ModelDiscovered::class, fn ($event): bool => $event->modelClass === 'Tests\\tmp\\Models\\User');
});

test('it fires ModelDiscovered events', function (): void {
    Event::fake();

    $service = new ModelScannerService;
    $service->scan();

    Event::assertDispatched(ModelDiscovered::class, fn ($event): bool => $event->modelClass === 'Tests\\tmp\\Models\\User');
    Event::assertDispatched(ModelDiscovered::class, fn ($event): bool => $event->modelClass === 'Tests\\tmp\\Models\\Post');
});

test('it handles global namespace models', function (): void {
    // We can't easily test global namespace models in this environment without affecting other tests
    // but we can mock the getClassFromFile method or just rely on the implementation logic.
    // For now, let's just test that the logic in getClassFromFile handles it.

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
