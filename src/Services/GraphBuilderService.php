<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use ReflectionClass;
use Throwable;

/**
 * Class GraphBuilderService
 *
 * Orchestrates the building of the model graph by coordinating scanner,
 * resolver, and inspector services.
 */
class GraphBuilderService
{
    /**
     * GraphBuilderService constructor.
     */
    public function __construct(
        protected ModelScannerService $scanner,
        protected RelationshipResolverService $resolver,
        protected SchemaInspectorService $inspector
    ) {}

    /**
     * Get the list of models from the scanner.
     *
     * @return array<int, string>
     */
    public function getModels(): array
    {
        return $this->scanner->scan();
    }

    /**
     * Generate the model graph data.
     *
     * @param  array<int, string>|null  $models
     * @param  (callable(string): void)|null  $onProgress
     * @return array<string, mixed>
     */
    public function generate(?array $models = null, ?callable $onProgress = null): array
    {
        $models ??= $this->getModels();

        $nodes = [];
        $edges = [];

        foreach ($models as $model) {
            /** @var class-string<Model> $model */
            if (! class_exists($model)) {
                continue;
            }

            try {
                $reflection = new ReflectionClass($model);

                if (! $reflection->isSubclassOf(Model::class)) {
                    continue;
                }

                /** @var Model $instance */
                $instance = new $model;

                $modelData = $this->inspector->inspect($model);
                /** @var array<string, array<string, mixed>> $relationships */
                $relationships = $this->resolver->resolve($model);

                $nodes[] = [
                    'id' => $model,
                    'name' => $reflection->getShortName(),
                    'table' => $instance->getTable(),
                    'columns' => $modelData['columns'] ?? [],
                    'relationships_count' => count($relationships),
                ];

                foreach ($relationships as $relName => $relData) {
                    /** @var class-string $relatedModel */
                    $relatedModel = $relData['related'];

                    if (! class_exists($relatedModel)) {
                        continue;
                    }

                    $relatedReflection = new ReflectionClass($relatedModel);

                    $edges[] = [
                        'id' => strtolower($reflection->getShortName().'_'.$relName.'_'.$relatedReflection->getShortName()),
                        'source' => $model,
                        'target' => $relatedModel,
                        'type' => $relData['type'],
                        'label' => $relName,
                        'metadata' => $relData,
                    ];
                }
            } catch (Throwable) {
                continue;
            }

            if ($onProgress !== null) {
                $onProgress($model);
            }
        }

        return [
            'meta' => [
                'generated_at' => Carbon::now()->toIso8601String(),
                'environment' => App::environment(),
                'model_count' => count($nodes),
                'relationship_count' => count($edges),
            ],
            'nodes' => $nodes,
            'edges' => $edges,
        ];
    }
}
