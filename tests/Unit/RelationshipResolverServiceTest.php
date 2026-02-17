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
        return $this->morphTo('morph_to_relation', 'morph_to_relation_type', 'morph_to_relation_id');
    }

    public function morphToManyRelation(): MorphToMany
    {
        return $this->morphToMany(RelatedModel::class, 'taggable');
    }
}

uses(TestCase::class);

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
        'method' => 'hasOneRelation',
        'type' => 'HasOne',
        'target' => RelatedModel::class,
    ]);

    expect($relationships['hasManyRelation'])->toMatchArray([
        'method' => 'hasManyRelation',
        'type' => 'HasMany',
        'target' => RelatedModel::class,
    ]);

    expect($relationships['belongsToRelation'])->toMatchArray([
        'method' => 'belongsToRelation',
        'type' => 'BelongsTo',
        'target' => RelatedModel::class,
    ]);

    expect($relationships['belongsToManyRelation'])->toMatchArray([
        'method' => 'belongsToManyRelation',
        'type' => 'BelongsToMany',
        'target' => RelatedModel::class,
    ]);

    expect($relationships['hasOneThroughRelation'])->toMatchArray([
        'method' => 'hasOneThroughRelation',
        'type' => 'HasOneThrough',
        'target' => RelatedModel::class,
    ]);

    expect($relationships['hasManyThroughRelation'])->toMatchArray([
        'method' => 'hasManyThroughRelation',
        'type' => 'HasManyThrough',
        'target' => RelatedModel::class,
    ]);

    expect($relationships['morphOneRelation'])->toMatchArray([
        'method' => 'morphOneRelation',
        'type' => 'MorphOne',
        'target' => RelatedModel::class,
    ]);

    expect($relationships['morphManyRelation'])->toMatchArray([
        'method' => 'morphManyRelation',
        'type' => 'MorphMany',
        'target' => RelatedModel::class,
    ]);

    expect($relationships['morphToRelation'])->toMatchArray([
        'method' => 'morphToRelation',
        'type' => 'MorphTo',
    ]);

    expect($relationships['morphToManyRelation'])->toMatchArray([
        'method' => 'morphToManyRelation',
        'type' => 'MorphToMany',
        'target' => RelatedModel::class,
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
