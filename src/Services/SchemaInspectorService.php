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
        /** @var bool $useSchemaInspection */
        $useSchemaInspection = Config::get('model-graph.scan.use_schema_inspection', true);

        if (! $useSchemaInspection) {
            return $this->getEmptyResponse();
        }

        /** @var bool $fakeSchema */
        $fakeSchema = Config::get('model-graph.scan.fake_schema', false);

        if ($fakeSchema) {
            return $this->getEmptyResponse();
        }

        try {
            /** @var Model $instance */
            $instance = new $model;
            $table = $instance->getTable();

            if (! Schema::hasTable($table)) {
                return $this->getEmptyResponse();
            }

            /** @var array<int, array<string, mixed>> $columns */
            $columns = Schema::getColumns($table);
            /** @var array<int, array<string, mixed>> $indexes */
            $indexes = Schema::getIndexes($table);
            /** @var array<int, array<string, mixed>> $foreignKeys */
            $foreignKeys = Schema::getForeignKeys($table);

            return [
                'columns' => $this->formatColumns($columns, $indexes),
                'foreign_keys' => $this->formatForeignKeys($foreignKeys),
            ];
        } catch (\Throwable) {
            return $this->getEmptyResponse();
        }
    }

    /**
     * Format the columns and include index information.
     *
     * @param  array<int, array<string, mixed>>  $columns
     * @param  array<int, array<string, mixed>>  $indexes
     * @return array<int, array<string, mixed>>
     */
    protected function formatColumns(array $columns, array $indexes): array
    {
        return array_map(function (array $column) use ($indexes): array {
            /** @var string $columnName */
            $columnName = $column['name'];
            $column['indexes'] = array_values(array_filter($indexes, function (array $index) use ($columnName): bool {
                /** @var array<int, string> $indexColumns */
                $indexColumns = $index['columns'];

                return in_array($columnName, $indexColumns, true);
            }));

            return $column;
        }, $columns);
    }

    /**
     * Format the foreign keys.
     *
     * @param  array<int, array<string, mixed>>  $foreignKeys
     * @return array<int, array<string, mixed>>
     */
    protected function formatForeignKeys(array $foreignKeys): array
    {
        return array_map(fn (array $foreignKey): array => [
            'name' => $foreignKey['name'],
            'columns' => $foreignKey['columns'],
            'foreign_table' => $foreignKey['foreign_table'],
            'foreign_columns' => $foreignKey['foreign_columns'],
            'on_update' => $foreignKey['on_update'],
            'on_delete' => $foreignKey['on_delete'],
        ], $foreignKeys);
    }

    /**
     * Get an empty response structure.
     *
     * @return array<string, mixed>
     */
    protected function getEmptyResponse(): array
    {
        return [
            'columns' => [],
            'foreign_keys' => [],
        ];
    }
}
