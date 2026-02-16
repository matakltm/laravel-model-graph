<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Services;

use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Class RelationshipResolverService
 *
 * Resolves relationships between Eloquent models using reflection.
 */
class RelationshipResolverService
{
    /** @var array<string, \Illuminate\Database\Eloquent\Model> */
    private array $modelInstances = [];

    /**
     * Resolve relationships for a given model.
     *
     * @param string $model
     * @return array<int, array<string, mixed>>
     */
    public function resolve(string $model): array
    {
        if (! class_exists($model)) {
            return [];
        }

        $reflection = new ReflectionClass($model);
        $relationships = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Relationships usually don't have required parameters
            if ($method->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            // Skip methods from base Model class and other common traits/classes if needed
            $declaringClass = $method->getDeclaringClass()->getName();
            if ($declaringClass === \Illuminate\Database\Eloquent\Model::class ||
                str_starts_with($declaringClass, 'Illuminate\\')) {
                continue;
            }

            try {
                $isRelation = false;
                $returnType = $method->getReturnType();

                if ($returnType instanceof ReflectionNamedType && is_subclass_of($returnType->getName(), Relation::class)) {
                    $isRelation = true;
                } elseif ($returnType === null) {
                    // Fallback: try calling the method to see if it returns a Relation instance
                    $instance = $this->getModelInstance($model);
                    $result = $method->invoke($instance);
                    if ($result instanceof Relation) {
                        $isRelation = true;
                    }
                }

                if ($isRelation) {
                    $relationships[] = $this->extractRelationshipData($model, $method);
                }
            } catch (\Throwable) {
                // Skip if error occurs during invocation
                continue;
            }
        }

        return $relationships;
    }

    /**
     * Get a cached instance of the model.
     */
    private function getModelInstance(string $model): \Illuminate\Database\Eloquent\Model
    {
        if (! isset($this->modelInstances[$model])) {
            /** @var \Illuminate\Database\Eloquent\Model $instance */
            $instance = new $model;
            $this->modelInstances[$model] = $instance;
        }

        return $this->modelInstances[$model];
    }

    /**
     * Extract relationship data.
     *
     * @param string $model
     * @param ReflectionMethod $method
     * @return array<string, mixed>
     */
    private function extractRelationshipData(string $model, ReflectionMethod $method): array
    {
        $instance = $this->getModelInstance($model);

        /** @var Relation<\Illuminate\Database\Eloquent\Model, \Illuminate\Database\Eloquent\Model, mixed> $relation */
        $relation = $method->invoke($instance);

        return [
            'method' => $method->getName(),
            'type' => (new ReflectionClass($relation))->getShortName(),
            'target' => get_class($relation->getRelated()),
            'metadata' => [
                'foreign_key' => $this->getForeignKey($relation),
                'owner_key' => $this->getOwnerKey($relation),
            ],
        ];
    }

    /**
     * Get foreign key from relation if possible.
     *
     * @param Relation<\Illuminate\Database\Eloquent\Model, \Illuminate\Database\Eloquent\Model, mixed> $relation
     */
    private function getForeignKey(Relation $relation): ?string
    {
        if (method_exists($relation, 'getForeignKeyName')) {
            /** @var string $key */
            $key = $relation->getForeignKeyName();
            return $key;
        }
        return null;
    }

    /**
     * Get owner key from relation if possible.
     *
     * @param Relation<\Illuminate\Database\Eloquent\Model, \Illuminate\Database\Eloquent\Model, mixed> $relation
     */
    private function getOwnerKey(Relation $relation): ?string
    {
        if (method_exists($relation, 'getOwnerKeyName')) {
            /** @var string $key */
            $key = $relation->getOwnerKeyName();
            return $key;
        }
        if (method_exists($relation, 'getLocalKeyName')) {
            /** @var string $key */
            $key = $relation->getLocalKeyName();
            return $key;
        }
        return null;
    }
}
