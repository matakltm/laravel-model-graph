<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

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
     * @param  class-string<Model>  $model
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
