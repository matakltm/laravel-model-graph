<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Matakltm\LaravelModelGraph\Services\SchemaInspectorService;
use Tests\TestCase;

uses(TestCase::class);

class TestModel extends Model
{
    protected $table = 'test_models';
}

test('it inspects model correctly', function (): void {
    Schema::create('test_models', function (Blueprint $table) {
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

    // Check index
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
