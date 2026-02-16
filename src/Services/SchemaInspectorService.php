<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Services;

/**
 * Class SchemaInspectorService
 *
 * Inspects the database schema for additional model information.
 */
class SchemaInspectorService
{
    /**
     * Inspect the schema for a given model.
     *
     * @return array<string, mixed>
     */
    public function inspect(string $model): array
    {
        if (! class_exists($model)) {
            return [];
        }

        /** @var \Illuminate\Database\Eloquent\Model $instance */
        $instance = new $model;

        return [
            'fillable' => $instance->getFillable(),
        ];
    }
}
