<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Services;

use Illuminate\Database\Eloquent\Model;
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
     * @return array<string, mixed>
     */
    public function inspect(string $model): array
    {
        if (! class_exists($model) || ! is_subclass_of($model, Model::class)) {
            return [
                'columns' => [],
                'foreign_keys' => [],
            ];
        }

        /** @var bool|null $useSchemaInspection */
        $useSchemaInspection = Config::get('model-graph.scan.use_schema_inspection');
        if (! ($useSchemaInspection ?? true)) {
            return [
                'columns' => [],
                'foreign_keys' => [],
            ];
        }

        /** @var bool|null $fakeSchema */
        $fakeSchema = Config::get('model-graph.scan.fake_schema');
        if ($fakeSchema ?? false) {
            return [
                'columns' => [],
                'foreign_keys' => [],
            ];
        }

        /** @var Model $instance */
        $instance = new $model;
        $table = $instance->getTable();

        if (! Schema::hasTable($table)) {
            return [
                'columns' => [],
                'foreign_keys' => [],
            ];
        }

        return [
            'columns' => $this->getColumns($table),
            'indexes' => Schema::getIndexes($table),
            'foreign_keys' => Schema::getForeignKeys($table),
            'fillable' => $instance->getFillable(),
        ];
    }

    /**
     * Get columns metadata.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getColumns(string $table): array
    {
        /** @var array<int, array<string, mixed>> $columns */
        $columns = Schema::getColumns($table);

        /** @var array<int, array{name: string, columns: string[], type: string, unique: bool}> $indexes */
        $indexes = Schema::getIndexes($table);

        return array_map(function ($column) use ($indexes) {
            /** @var array<string, mixed> $column */
            $columnIndexes = array_filter($indexes, function ($index) use ($column) {
                return in_array($column['name'], $index['columns'], true);
            });

            return array_merge($column, [
                'indexes' => array_values($columnIndexes),
            ]);
        }, $columns);
    }
}
