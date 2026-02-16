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
    if (! is_dir($this->testModelsPath)) {
        File::makeDirectory($this->testModelsPath, 0777, true);
    }

    // Create dummy model files
    File::put($this->testModelsPath.'/User.php', "<?php namespace Tests\\tmp\\Models; class User extends \Illuminate\Database\Eloquent\Model {}");
    File::put($this->testModelsPath.'/Post.php', "<?php namespace Tests\\tmp\\Models; class Post extends \Illuminate\Database\Eloquent\Model {}");
    File::put($this->testModelsPath.'/NotAModel.php', '<?php namespace Tests\\tmp\\Models; class NotAModel {}');

    Config::set('model-graph.scan.models_paths', [$this->testModelsPath]);
    Config::set('model-graph.cache_duration', 3600);
});

afterEach(function () {
    File::deleteDirectory(__DIR__.'/../tmp');
    Cache::flush();
});

test('it scans configured paths for models', function () {
    $service = new ModelScannerService;
    $models = $service->scan();

    expect($models)->toContain('Tests\\tmp\\Models\\User');
    expect($models)->toContain('Tests\\tmp\\Models\\Post');
    expect($models)->not->toContain('Tests\\tmp\\Models\\NotAModel');
});

test('it respects ignore_models filter', function () {
    Config::set('model-graph.scan.ignore_models', ['Tests\\tmp\\Models\\Post']);

    $service = new ModelScannerService;
    $models = $service->scan();

    expect($models)->toContain('Tests\\tmp\\Models\\User');
    expect($models)->not->toContain('Tests\\tmp\\Models\\Post');
});

test('it respects include_only filter', function () {
    Config::set('model-graph.scan.include_only', ['Tests\\tmp\\Models\\User']);

    $service = new ModelScannerService;
    $models = $service->scan();

    expect($models)->toContain('Tests\\tmp\\Models\\User');
    expect($models)->not->toContain('Tests\\tmp\\Models\\Post');
});

test('it caches results and still fires events', function () {
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
    Event::assertDispatched(ModelDiscovered::class, function ($event) {
        return $event->modelClass === 'Tests\\tmp\\Models\\User';
    });
});

test('it fires ModelDiscovered events', function () {
    Event::fake();

    $service = new ModelScannerService;
    $service->scan();

    Event::assertDispatched(ModelDiscovered::class, function ($event) {
        return $event->modelClass === 'Tests\\tmp\\Models\\User';
    });
    Event::assertDispatched(ModelDiscovered::class, function ($event) {
        return $event->modelClass === 'Tests\\tmp\\Models\\Post';
    });
});

test('it handles global namespace models', function () {
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
