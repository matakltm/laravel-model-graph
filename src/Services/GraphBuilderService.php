<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Matakltm\LaravelModelGraph\Events\ModelGraphGenerated;

/**
 * Class GraphBuilderService
 *
 * Orchestrates the building of the model graph by coordinating scanner,
 * resolver, and inspector services.
 */
class GraphBuilderService
{
    /** @var array<class-string<\Illuminate\Database\Eloquent\Model>, bool> */
    private array $recursionStack = [];

    /** @var array<int, array<int, class-string<\Illuminate\Database\Eloquent\Model>>> */
    private array $loops = [];

    /** @var array<int, string> */
    private array $warnings = [];

    /**
     * GraphBuilderService constructor.
     */
    public function __construct(
        protected ModelScannerService $modelScanner,
        protected RelationshipResolverService $relationshipResolver,
        protected SchemaInspectorService $schemaInspector
    ) {}

    /**
     * Get the list of models from the scanner.
     *
     * @return array<int, class-string<Model>>
     */
    public function getModels(): array
    {
        return $this->modelScanner->scan();
    }

    /**
     * Generate the model graph data.
     *
     * @param  array<int, class-string<Model>>|null  $models
     * @param  (callable(string): void)|null  $onProgress
     * @return array<string, mixed>
     */
    public function generate(?array $models = null, ?callable $onProgress = null): array
    {
        $this->warnings = [];
        $models ??= $this->modelScanner->scan();
        $nodes = [];
        $edges = [];
        /** @var array<class-string<\Illuminate\Database\Eloquent\Model>, array<int, class-string<\Illuminate\Database\Eloquent\Model>>> $graph */
        $graph = [];

        foreach ($models as $modelClass) {
            if ($onProgress) {
                $onProgress($modelClass);
            }

            try {
                $inspection = $this->schemaInspector->inspect($modelClass);

                $nodes[$modelClass] = [
                    'name' => class_basename($modelClass),
                    'namespace' => $modelClass,
                    'fillable' => $inspection['fillable'] ?? [],
                    'inLoops' => false,
                    'loopSeverity' => 0,
                ];
            } catch (\Throwable $e) {
                $this->warnings[] = sprintf('Error inspecting model %s: ', $modelClass).$e->getMessage();
                // Still add the node but with limited info
                $nodes[$modelClass] = [
                    'name' => class_basename($modelClass),
                    'namespace' => $modelClass,
                    'fillable' => [],
                    'inLoops' => false,
                    'loopSeverity' => 0,
                ];
            }

            try {
                $relationships = $this->relationshipResolver->resolve($modelClass);
                foreach ($relationships as $rel) {
                    /** @var string|null $targetClass */
                    $targetClass = $rel['target'];

                    /** @var string $type */
                    $type = $rel['type'];

                    $edges[] = [
                        'source' => $modelClass,
                        'target' => $targetClass,
                        'type' => $type,
                        'method' => $rel['method'],
                        'metadata' => $rel['metadata'] ?? [],
                        'direction' => $this->getDirection($type),
                        'cardinality' => $this->getCardinality($type),
                    ];

                    if ($targetClass) {
                        $graph[$modelClass][] = $targetClass;
                    }
                }
            } catch (\Throwable $e) {
                $this->warnings[] = sprintf('Error resolving relationships for %s: ', $modelClass).$e->getMessage();
            }
        }

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

        $data = [
            'version' => '1.0.0',
            'timestamp' => Carbon::now()->toIso8601String(),
            'totalModels' => count($nodes),
            'totalRelationships' => count($edges),
            'warnings' => $this->warnings,
            'options' => [
                'json_options' => $jsonOptions,
            ],
            'models' => array_values($nodes),
            'relationships' => $edges,
            'loops' => $uniqueLoops,
        ];

        event(new ModelGraphGenerated($data));

        return $data;
    }

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

        /** @var int $maxDepth */
        $maxDepth = Config::get('model-graph.relationships.max_depth', 5);

        foreach (array_keys($graph) as $node) {
            $this->dfs($node, $graph, [], 0, $maxDepth);
        }
    }

    /**
     * Depth-First Search to find cycles.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $node
     * @param  array<class-string<\Illuminate\Database\Eloquent\Model>, array<int, class-string<\Illuminate\Database\Eloquent\Model>>>  $graph
     * @param  array<int, class-string<\Illuminate\Database\Eloquent\Model>>  $path
     */
    private function dfs(string $node, array $graph, array $path, int $depth, int $maxDepth): void
    {
        if ($depth > $maxDepth) {
            return;
        }

        $this->recursionStack[$node] = true;
        $path[] = $node;

        if (isset($graph[$node])) {
            foreach ($graph[$node] as $neighbor) {
                if (isset($this->recursionStack[$neighbor])) {
                    $loopStartIdx = array_search($neighbor, $path, true);
                    if ($loopStartIdx !== false) {
                        /** @var array<int, class-string<\Illuminate\Database\Eloquent\Model>> $loop */
                        $loop = array_slice($path, (int) $loopStartIdx);
                        $this->loops[] = $loop;
                    }
                } else {
                    $this->dfs($neighbor, $graph, $path, $depth + 1, $maxDepth);
                }
            }
        }

        unset($this->recursionStack[$node]);
    }

    /**
     * Filter loops to get only unique cycles.
     *
     * @return array<int, array<int, string>>
     */
    private function getUniqueLoops(): array
    {
        $unique = [];
        $hashes = [];

        foreach ($this->loops as $loop) {
            $sorted = $loop;
            sort($sorted);
            $hash = implode('|', $sorted);
            if (! in_array($hash, $hashes)) {
                $hashes[] = $hash;
                $unique[] = $loop;
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
