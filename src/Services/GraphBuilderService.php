<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Matakltm\LaravelModelGraph\Events\ModelGraphGenerated;
use Throwable;

/**
 * Class GraphBuilderService
 *
 * Orchestrates the building of the model graph by coordinating scanner,
 * resolver, and inspector services.
 */
class GraphBuilderService
{
    /** @var array<string, bool> */
    private array $recursionStack = [];

    /** @var array<int, array<int, string>> */
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
     * @param  array<int, string>|null  $models
     * @param  (callable(string): void)|null  $onProgress
     * @return array<string, mixed>
     */
    public function generate(?array $models = null, ?callable $onProgress = null): array
    {
        $this->warnings = [];
        $this->loops = [];
        $this->recursionStack = [];

        $models ??= $this->modelScanner->scan();
        $nodes = [];
        $edges = [];
        /** @var array<string, array<int, string>> $graph */
        $graph = [];

        foreach ($models as $modelClass) {
            if ($onProgress !== null) {
                $onProgress($modelClass);
            }

            $inspection = [];
            try {
                $inspection = $this->schemaInspector->inspect($modelClass);
            } catch (Throwable $e) {
                $this->warnings[] = sprintf('Error inspecting model %s: %s', $modelClass, $e->getMessage());
            }

            $relationships = [];
            try {
                $relationships = $this->relationshipResolver->resolve($modelClass);
            } catch (Throwable $e) {
                $this->warnings[] = sprintf('Error resolving relationships for %s: %s', $modelClass, $e->getMessage());
            }

            $nodes[$modelClass] = [
                'id' => $modelClass,
                'name' => class_basename($modelClass),
                'namespace' => $modelClass,
                'table' => $inspection['table'] ?? $this->guessTable($modelClass),
                'columns' => $inspection['columns'] ?? [],
                'fillable' => $inspection['fillable'] ?? [],
                'relationships_count' => count($relationships),
                'inLoops' => false,
                'loopSeverity' => 0,
            ];

            foreach ($relationships as $relName => $relData) {
                /** @var string|null $targetClass */
                $targetClass = $relData['target'] ?? null;

                if ($targetClass !== null) {
                    $edges[] = [
                        'id' => strtolower(class_basename($modelClass).'_'.$relName.'_'.class_basename($targetClass)),
                        'source' => $modelClass,
                        'target' => $targetClass,
                        'type' => $relData['type'] ?? 'Unknown',
                        'label' => $relName,
                        'metadata' => $relData['metadata'] ?? [],
                    ];

                    $graph[$modelClass][] = $targetClass;
                }
            }
        }

        /** @var int $maxDepth */
        $maxDepth = Config::get('model-graph.relationships.max_depth', 5);

        foreach (array_keys($graph) as $node) {
            $this->dfs($node, $graph, [], 0, $maxDepth);
        }

        $uniqueLoops = $this->getUniqueLoops();

        foreach ($uniqueLoops as $loop) {
            foreach ($loop as $nodeId) {
                if (isset($nodes[$nodeId])) {
                    $nodes[$nodeId]['inLoops'] = true;
                    $nodes[$nodeId]['loopSeverity']++;
                }
            }
        }

        /** @var int $jsonOptions */
        $jsonOptions = Config::get('model-graph.json_options', JSON_PRETTY_PRINT);

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
            'meta' => [
                'generated_at' => Carbon::now()->toIso8601String(),
                'environment' => App::environment(),
                'model_count' => count($nodes),
                'relationship_count' => count($edges),
            ],
        ];

        event(new ModelGraphGenerated($data));

        return $data;
    }

    /**
     * Guess table name if not provided.
     */
    private function guessTable(string $modelClass): string
    {
        if (class_exists($modelClass)) {
            /** @var Model $instance */
            $instance = new $modelClass;

            return $instance->getTable();
        }

        return strtolower(class_basename($modelClass)).'s';
    }

    /**
     * Depth-First Search to find cycles.
     *
     * @param  array<string, array<int, string>>  $graph
     * @param  array<int, string>  $path
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
                        /** @var array<int, string> $loop */
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

        return $unique;
    }
}
