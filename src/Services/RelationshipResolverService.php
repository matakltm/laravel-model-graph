<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

/**
 * Class RelationshipResolverService
 *
 * Resolves relationships between Eloquent models using reflection.
 */
class RelationshipResolverService
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $cache = [];

    /**
     * Resolve relationships for a given model.
     *
     * @return array<string, mixed>
     */
    public function resolve(string $model): array
    {
        if (isset($this->cache[$model])) {
            return $this->cache[$model];
        }

        try {
            if (! class_exists($model)) {
                return [];
            }

            $instance = app()->make($model);
            if (! $instance instanceof Model) {
                return [];
            }
        } catch (Throwable) {
            return [];
        }

        $relationships = [];
        $reflection = new ReflectionClass($model);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($this->shouldSkipMethod($method)) {
                continue;
            }

            try {
                $return = $method->invoke($instance);

                if ($return instanceof Relation) {
                    $relationships[$method->getName()] = $this->extractRelationshipData($return, $method->getName());
                }
            } catch (Throwable) {
                continue;
            }
        }

        return $this->cache[$model] = $relationships;
    }

    /**
     * Determine if a method should be skipped during relationship resolution.
     */
    private function shouldSkipMethod(ReflectionMethod $method): bool
    {
        $skippedClasses = [
            Model::class,
            \Illuminate\Database\Eloquent\Concerns\HasRelationships::class,
            \Illuminate\Database\Eloquent\Concerns\HasAttributes::class,
            \Illuminate\Database\Eloquent\Concerns\HidesAttributes::class,
            \Illuminate\Database\Eloquent\Concerns\GuardsAttributes::class,
            \Illuminate\Database\Eloquent\Concerns\HasEvents::class,
            \Illuminate\Database\Eloquent\Concerns\HasGlobalScopes::class,
            \Illuminate\Database\Eloquent\Concerns\HasTimestamps::class,
            \Illuminate\Database\Eloquent\Concerns\QueriesRelationships::class,
        ];
        if ($method->getNumberOfParameters() > 0) {
            return true;
        }

        if ($method->isStatic()) {
            return true;
        }

        return in_array($method->getDeclaringClass()->getName(), $skippedClasses);
    }

    /**
     * Extract metadata from a relationship.
     *
     * @param  Relation<Model, Model, mixed>  $relation
     * @return array<string, mixed>
     */
    private function extractRelationshipData(Relation $relation, string $name): array
    {
        $reflection = new ReflectionClass($relation);
        $type = $reflection->getShortName();
        $relatedModel = $relation->getRelated()::class;

        $data = [
            'name' => $name,
            'type' => $type,
            'related' => $relatedModel,
        ];

        if ($relation instanceof HasOneOrMany) {
            $data['foreign_key'] = $relation->getForeignKeyName();
            $data['local_key'] = $relation->getLocalKeyName();
        }

        if ($relation instanceof BelongsTo) {
            $data['foreign_key'] = $relation->getForeignKeyName();
            $data['owner_key'] = $relation->getOwnerKeyName();
        }

        if ($relation instanceof BelongsToMany) {
            $data['pivot_table'] = $relation->getTable();
            $data['foreign_pivot_key'] = $relation->getForeignPivotKeyName();
            $data['related_pivot_key'] = $relation->getRelatedPivotKeyName();
            $data['pivot_columns'] = $this->getProtectedProperty($relation, 'pivotColumns', []);
        }

        if ($relation instanceof HasOneThrough || $relation instanceof HasManyThrough) {
            $throughParent = $this->getProtectedProperty($relation, 'throughParent');
            $data['through_model'] = is_object($throughParent) ? $throughParent::class : null;
            $data['first_key'] = $this->getProtectedProperty($relation, 'firstKey');
            $data['second_key'] = $this->getProtectedProperty($relation, 'secondKey');
            $data['local_key'] = $this->getProtectedProperty($relation, 'localKey');
            $data['second_local_key'] = $this->getProtectedProperty($relation, 'secondLocalKey');
        }

        if ($relation instanceof MorphOneOrMany) {
            $data['morph_type'] = $relation->getMorphType();
        }

        if ($relation instanceof MorphTo) {
            $data['morph_type'] = $relation->getMorphType();
            $data['foreign_key'] = $relation->getForeignKeyName();
        }

        if ($relation instanceof MorphToMany) {
            $data['morph_type'] = $relation->getMorphType();
        }

        return $data;
    }

    /**
     * Get a protected property from an object using reflection.
     */
    private function getProtectedProperty(object $object, string $property, mixed $default = null): mixed
    {
        try {
            $reflection = new ReflectionClass($object);
            $prop = null;
            $currentClass = $reflection;
            while ($currentClass) {
                if ($currentClass->hasProperty($property)) {
                    $prop = $currentClass->getProperty($property);
                    break;
                }

                $currentClass = $currentClass->getParentClass();
            }

            if ($prop) {
                return $prop->getValue($object);
            }
        } catch (Throwable) {
            // Silently fail
        }

        return $default;
    }
}
