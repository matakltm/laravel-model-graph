<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Matakltm\LaravelModelGraph\Services\RelationshipResolverService;
use Tests\TestCase;

uses(TestCase::class);

class RelatedModel extends Model {}
class ThroughModel extends Model {}

class MockModel extends Model
{
    public function hasOneRelation(): HasOne
    {
        return $this->hasOne(RelatedModel::class, 'foreign_key', 'local_key');
    }

    public function hasManyRelation(): HasMany
    {
        return $this->hasMany(RelatedModel::class, 'foreign_key', 'local_key');
    }

    public function belongsToRelation(): BelongsTo
    {
        return $this->belongsTo(RelatedModel::class, 'foreign_key', 'owner_key');
    }

    public function belongsToManyRelation(): BelongsToMany
    {
        return $this->belongsToMany(RelatedModel::class, 'pivot_table', 'foreign_pivot_key', 'related_pivot_key')
            ->withPivot('column1', 'column2');
    }

    public function hasOneThroughRelation(): HasOneThrough
    {
        return $this->hasOneThrough(RelatedModel::class, ThroughModel::class, 'first_key', 'second_key', 'local_key', 'second_local_key');
    }

    public function hasManyThroughRelation(): HasManyThrough
    {
        return $this->hasManyThrough(RelatedModel::class, ThroughModel::class, 'first_key', 'second_key', 'local_key', 'second_local_key');
    }

    public function morphOneRelation(): MorphOne
    {
        return $this->morphOne(RelatedModel::class, 'morphable');
    }

    public function morphManyRelation(): MorphMany
    {
        return $this->morphMany(RelatedModel::class, 'morphable');
    }

    public function morphToRelation(): MorphTo
    {
        return $this->morphTo();
    }

    public function morphToManyRelation(): MorphToMany
    {
        return $this->morphToMany(RelatedModel::class, 'taggable');
    }

    public function notARelation(): string
    {
        return 'string';
    }

    public static function staticMethod(): string
    {
        return 'static';
    }

    public function methodWithParams(string $param): string
    {
        return $param;
    }
}

test('it can be instantiated', function (): void {
    $service = new RelationshipResolverService;
    expect($service)->toBeInstanceOf(RelationshipResolverService::class);
});

test('it resolves all relationship types correctly', function (): void {
    $service = new RelationshipResolverService;
    $relationships = $service->resolve(MockModel::class);

    expect($relationships)->toBeArray()
        ->toHaveCount(10);

    expect($relationships['hasOneRelation'])->toMatchArray([
        'name' => 'hasOneRelation',
        'type' => 'HasOne',
        'related' => RelatedModel::class,
        'foreign_key' => 'foreign_key',
        'local_key' => 'local_key',
    ]);

    expect($relationships['hasManyRelation'])->toMatchArray([
        'name' => 'hasManyRelation',
        'type' => 'HasMany',
        'related' => RelatedModel::class,
        'foreign_key' => 'foreign_key',
        'local_key' => 'local_key',
    ]);

    expect($relationships['belongsToRelation'])->toMatchArray([
        'name' => 'belongsToRelation',
        'type' => 'BelongsTo',
        'related' => RelatedModel::class,
        'foreign_key' => 'foreign_key',
        'owner_key' => 'owner_key',
    ]);

    expect($relationships['belongsToManyRelation'])->toMatchArray([
        'name' => 'belongsToManyRelation',
        'type' => 'BelongsToMany',
        'related' => RelatedModel::class,
        'pivot_table' => 'pivot_table',
        'foreign_pivot_key' => 'foreign_pivot_key',
        'related_pivot_key' => 'related_pivot_key',
        'pivot_columns' => ['column1', 'column2'],
    ]);

    expect($relationships['hasOneThroughRelation'])->toMatchArray([
        'name' => 'hasOneThroughRelation',
        'type' => 'HasOneThrough',
        'related' => RelatedModel::class,
        'through_model' => ThroughModel::class,
        'first_key' => 'first_key',
        'second_key' => 'second_key',
        'local_key' => 'local_key',
        'second_local_key' => 'second_local_key',
    ]);

    expect($relationships['hasManyThroughRelation'])->toMatchArray([
        'name' => 'hasManyThroughRelation',
        'type' => 'HasManyThrough',
        'related' => RelatedModel::class,
        'through_model' => ThroughModel::class,
        'first_key' => 'first_key',
        'second_key' => 'second_key',
        'local_key' => 'local_key',
        'second_local_key' => 'second_local_key',
    ]);

    expect($relationships['morphOneRelation'])->toMatchArray([
        'name' => 'morphOneRelation',
        'type' => 'MorphOne',
        'related' => RelatedModel::class,
        'foreign_key' => 'morphable_id',
        'local_key' => 'id',
        'morph_type' => 'morphable_type',
    ]);

    expect($relationships['morphManyRelation'])->toMatchArray([
        'name' => 'morphManyRelation',
        'type' => 'MorphMany',
        'related' => RelatedModel::class,
        'foreign_key' => 'morphable_id',
        'local_key' => 'id',
        'morph_type' => 'morphable_type',
    ]);

    expect($relationships['morphToRelation'])->toMatchArray([
        'name' => 'morphToRelation',
        'type' => 'MorphTo',
        'morph_type' => 'morph_to_relation_type',
        'foreign_key' => 'morph_to_relation_id',
    ]);

    expect($relationships['morphToManyRelation'])->toMatchArray([
        'name' => 'morphToManyRelation',
        'type' => 'MorphToMany',
        'related' => RelatedModel::class,
        'morph_type' => 'taggable_type',
    ]);
});

test('it caches the resolved relationships', function (): void {
    $service = new RelationshipResolverService;
    $relationships1 = $service->resolve(MockModel::class);
    $relationships2 = $service->resolve(MockModel::class);

    expect($relationships1)->toBe($relationships2);
});

test('it returns empty array for non-existent classes', function (): void {
    $service = new RelationshipResolverService;
    expect($service->resolve('NonExistentClass'))->toBeArray()->toBeEmpty();
});

test('it returns empty array for non-model classes', function (): void {
    $service = new RelationshipResolverService;
    expect($service->resolve(\stdClass::class))->toBeArray()->toBeEmpty();
});
