<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Matakltm\LaravelModelGraph\Services\SchemaInspectorService;
use Tests\TestCase;

uses(TestCase::class);

class TestModel extends Model
{
    protected $table = 'test_models';

    protected $fillable = [
        'name',
        'email',
    ];
}

test('it inspects model correctly', function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        $this->markTestSkipped('SQLite extension not loaded.');
    }

    Schema::create('test_models', function (Illuminate\Database\Schema\Blueprint $table): void {
        $table->id();
        $table->string('name')->nullable();
        $table->string('email')->unique();
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    });

    $service = new SchemaInspectorService;
    $result = $service->inspect(TestModel::class);

    $nameColumn = null;
    foreach ($result['columns'] as $column) {
        if ($column['name'] === 'name') {
            $nameColumn = $column;
            break;
        }
    }

    expect($nameColumn)->not->toBeNull();
    expect($nameColumn['nullable'])->toBeTrue();

    expect($result['fillable'])->toContain('name');
    expect($result['fillable'])->toContain('email');
});

test('it can be instantiated', function (): void {
    $service = new SchemaInspectorService;
    expect($service)->toBeInstanceOf(SchemaInspectorService::class);
});

test('it inspects a model', function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        $this->markTestSkipped('SQLite extension not loaded.');
    }

    Schema::create('test_models', function (Illuminate\Database\Schema\Blueprint $table): void {
        $table->id();
        $table->string('name')->nullable();
        $table->string('email')->unique();
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    });

    $service = new SchemaInspectorService;
    $result = $service->inspect(TestModel::class);

    expect($result['columns'])->not->toBeEmpty();
    expect($result['foreign_keys'])->not->toBeEmpty();

    // Check column
    $emailColumn = null;
    foreach ($result['columns'] as $column) {
        if ($column['name'] === 'email') {
            $emailColumn = $column;
            break;
        }
    }

    expect($emailColumn['indexes'])->not->toBeEmpty();
    expect($emailColumn['indexes'][0]['unique'] ?? ($emailColumn['indexes'][0]['type'] === 'unique'))->toBeTruthy();

    // Check foreign key
    expect($result['foreign_keys'])->not->toBeEmpty();
    expect($result['foreign_keys'][0]['foreign_table'])->toBe('users');
    expect($result['foreign_keys'][0]['on_delete'])->toBe('cascade');
});

test('it returns empty if use_schema_inspection is disabled', function (): void {
    Config::set('model-graph.scan.use_schema_inspection', false);
    $service = new SchemaInspectorService;
    $result = $service->inspect(TestModel::class);

    expect($result['columns'])->toBeEmpty();
    expect($result['foreign_keys'])->toBeEmpty();
});

test('it returns empty if fake_schema is enabled', function (): void {
    Config::set('model-graph.scan.fake_schema', true);
    $service = new SchemaInspectorService;
    $result = $service->inspect(TestModel::class);

    expect($result['columns'])->toBeEmpty();
    expect($result['foreign_keys'])->toBeEmpty();
});

test('it returns empty if table does not exist', function (): void {
    $service = new SchemaInspectorService;
    $result = $service->inspect('NonExistentModelClass');

    expect($result['columns'])->toBeEmpty();
    expect($result['foreign_keys'])->toBeEmpty();
});
