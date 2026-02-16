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
     * @param string $model
     * @return array<string, mixed>
     */
    public function resolve(string $model): array
    {
        if (isset($this->cache[$model])) {
            return $this->cache[$model];
        }

        try {
            if (!class_exists($model)) {
                return [];
            }

            $instance = app()->make($model);
            if (!$instance instanceof Model) {
                return [];
            }
        } catch (Throwable $e) {
            return [];
        }

        $relationships = [];
        $reflection = new ReflectionClass($model);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($this->shouldSkipMethod($method, $model)) {
                continue;
            }

            try {
                $return = $method->invoke($instance);

                if ($return instanceof Relation) {
                    $relationships[$method->getName()] = $this->extractRelationshipData($return, $method->getName());
                }
            } catch (Throwable $e) {
                continue;
            }
        }

        return $this->cache[$model] = $relationships;
    }

    /**
     * Determine if a method should be skipped during relationship resolution.
     *
     * @param ReflectionMethod $method
     * @param string $model
     * @return bool
     */
    private function shouldSkipMethod(ReflectionMethod $method, string $model): bool
    {
        $skippedClasses = [
            Model::class,
            'Illuminate\Database\Eloquent\Concerns\HasRelationships',
            'Illuminate\Database\Eloquent\Concerns\HasAttributes',
            'Illuminate\Database\Eloquent\Concerns\HidesAttributes',
            'Illuminate\Database\Eloquent\Concerns\GuardsAttributes',
            'Illuminate\Database\Eloquent\Concerns\HasEvents',
            'Illuminate\Database\Eloquent\Concerns\HasGlobalScopes',
            'Illuminate\Database\Eloquent\Concerns\HasTimestamps',
            'Illuminate\Database\Eloquent\Concerns\QueriesRelationships',
        ];

        return $method->getNumberOfParameters() > 0
            || $method->isStatic()
            || in_array($method->getDeclaringClass()->getName(), $skippedClasses);
    }

    /**
     * Extract metadata from a relationship.
     *
     * @param Relation $relation
     * @param string $name
     * @return array<string, mixed>
     */
    private function extractRelationshipData(Relation $relation, string $name): array
    {
        $reflection = new ReflectionClass($relation);
        $type = $reflection->getShortName();
        $relatedModel = get_class($relation->getRelated());

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
            $data['through_model'] = get_class($this->getProtectedProperty($relation, 'throughParent'));
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
     *
     * @param object $object
     * @param string $property
     * @param mixed $default
     * @return mixed
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
                $prop->setAccessible(true);
                return $prop->getValue($object);
            }
        } catch (Throwable $e) {
            // Silently fail
        }
        return $default;
    }
}
