<?php

use Matakltm\LaravelModelGraph\Services\GraphBuilderService;
use Matakltm\LaravelModelGraph\Services\ModelScannerService;
use Matakltm\LaravelModelGraph\Services\RelationshipResolverService;
use Matakltm\LaravelModelGraph\Services\SchemaInspectorService;
use Tests\TestCase;
use Illuminate\Support\Facades\Config;

uses(TestCase::class);

test('it can be instantiated', function (): void {
    $service = app(GraphBuilderService::class);
    expect($service)->toBeInstanceOf(GraphBuilderService::class);
});

test('generate method returns the expected structure', function (): void {
    $service = app(GraphBuilderService::class);
    $data = $service->generate();

    expect($data)->toBeArray()
        ->toHaveKeys(['version', 'timestamp', 'totalModels', 'totalRelationships', 'warnings', 'models', 'relationships', 'loops']);
});

test('it detects loops correctly', function (): void {
    $modelScanner = Mockery::mock(ModelScannerService::class);
    $relationshipResolver = Mockery::mock(RelationshipResolverService::class);
    $schemaInspector = Mockery::mock(SchemaInspectorService::class);

    $modelScanner->shouldReceive('scan')->andReturn(['App\Models\User', 'App\Models\Post']);

    $relationshipResolver->shouldReceive('resolve')
        ->with('App\Models\User')
        ->andReturn([[
            'method' => 'posts',
            'type' => 'HasMany',
            'target' => 'App\Models\Post',
        ]]);

    $relationshipResolver->shouldReceive('resolve')
        ->with('App\Models\Post')
        ->andReturn([[
            'method' => 'user',
            'type' => 'BelongsTo',
            'target' => 'App\Models\User',
        ]]);

    $schemaInspector->shouldReceive('inspect')->andReturn(['fillable' => []]);

    $service = new GraphBuilderService($modelScanner, $relationshipResolver, $schemaInspector);
    $data = $service->generate();

    $userModel = collect($data['models'])->firstWhere('namespace', 'App\Models\User');
    $postModel = collect($data['models'])->firstWhere('namespace', 'App\Models\Post');

    expect($userModel['inLoops'])->toBeTrue()
        ->and($postModel['inLoops'])->toBeTrue()
        ->and($userModel['loopSeverity'])->toBeGreaterThan(0)
        ->and($data['loops'])->not->toBeEmpty();
});

test('it respects max_depth', function (): void {
    Config::set('model-graph.relationships.max_depth', 1);

    $modelScanner = Mockery::mock(ModelScannerService::class);
    $relationshipResolver = Mockery::mock(RelationshipResolverService::class);
    $schemaInspector = Mockery::mock(SchemaInspectorService::class);

    // User -> Post -> Comment -> User
    $modelScanner->shouldReceive('scan')->andReturn(['App\Models\User', 'App\Models\Post', 'App\Models\Comment']);

    $relationshipResolver->shouldReceive('resolve')->with('App\Models\User')->andReturn([['type' => 'HasMany', 'target' => 'App\Models\Post', 'method' => 'posts']]);
    $relationshipResolver->shouldReceive('resolve')->with('App\Models\Post')->andReturn([['type' => 'HasMany', 'target' => 'App\Models\Comment', 'method' => 'comments']]);
    $relationshipResolver->shouldReceive('resolve')->with('App\Models\Comment')->andReturn([['type' => 'BelongsTo', 'target' => 'App\Models\User', 'method' => 'user']]);

    $schemaInspector->shouldReceive('inspect')->andReturn(['fillable' => []]);

    $service = new GraphBuilderService($modelScanner, $relationshipResolver, $schemaInspector);
    $data = $service->generate();

    $userModel = collect($data['models'])->firstWhere('namespace', 'App\Models\User');
    expect($userModel['inLoops'])->toBeFalse();
});

test('it uses FQCN for source and target', function (): void {
    $modelScanner = Mockery::mock(ModelScannerService::class);
    $relationshipResolver = Mockery::mock(RelationshipResolverService::class);
    $schemaInspector = Mockery::mock(SchemaInspectorService::class);

    $modelScanner->shouldReceive('scan')->andReturn(['App\Models\User']);
    $relationshipResolver->shouldReceive('resolve')->andReturn([[
        'method' => 'posts',
        'type' => 'HasMany',
        'target' => 'App\Models\Post',
    ]]);
    $schemaInspector->shouldReceive('inspect')->andReturn(['fillable' => []]);

    $service = new GraphBuilderService($modelScanner, $relationshipResolver, $schemaInspector);
    $data = $service->generate();

    $rel = $data['relationships'][0];
    expect($rel['source'])->toBe('App\Models\User')
        ->and($rel['target'])->toBe('App\Models\Post');
});

test('it populates warnings on error', function (): void {
    $modelScanner = Mockery::mock(ModelScannerService::class);
    $relationshipResolver = Mockery::mock(RelationshipResolverService::class);
    $schemaInspector = Mockery::mock(SchemaInspectorService::class);

    $modelScanner->shouldReceive('scan')->andReturn(['App\Models\BadModel']);
    $schemaInspector->shouldReceive('inspect')->andThrow(new Exception('Failed to inspect'));
    $relationshipResolver->shouldReceive('resolve')->andThrow(new Exception('Failed to resolve'));

    $service = new GraphBuilderService($modelScanner, $relationshipResolver, $schemaInspector);
    $data = $service->generate();

    expect($data['warnings'])->toHaveCount(2)
        ->and($data['warnings'][0])->toContain('Error inspecting model')
        ->and($data['warnings'][1])->toContain('Error resolving relationships');
});
