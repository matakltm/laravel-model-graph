<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Services;

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
     * @return array<int, string>
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
        $models ??= $this->modelScanner->scan();
        $nodes = [];
        $edges = [];
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
                $this->warnings[] = "Error inspecting model {$modelClass}: ".$e->getMessage();
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
                    /** @var string $targetClass */
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

                    $graph[$modelClass][] = $targetClass;
                }
            } catch (\Throwable $e) {
                $this->warnings[] = "Error resolving relationships for {$modelClass}: ".$e->getMessage();
            }
        }

        $this->detectLoops($graph);

        $uniqueLoops = $this->getUniqueLoops();

        foreach ($uniqueLoops as $loop) {
            foreach ($loop as $nodeClass) {
                if (isset($nodes[$nodeClass])) {
                    $nodes[$nodeClass]['inLoops'] = true;
                    $nodes[$nodeClass]['loopSeverity']++;
                }
            }
        }

        /** @var int $jsonOptions */
        $jsonOptions = Config::get('model-graph.json_options', JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

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

    /**
     * Detect loops in the graph using DFS.
     *
     * @param  array<string, array<int, string>>  $graph
     */
    private function detectLoops(array $graph): void
    {
        $this->recursionStack = [];
        $this->loops = [];

        /** @var int $maxDepth */
        $maxDepth = Config::get('model-graph.relationships.max_depth', 5);

        foreach (array_keys($graph) as $node) {
            $this->dfs($node, $graph, [], 0, $maxDepth);
        }
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
                    $loopStartIdx = array_search($neighbor, $path);
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

    /**
     * Get the direction of the relationship.
     */
    private function getDirection(string $type): string
    {
        return match ($type) {
            'BelongsTo', 'BelongsToMany', 'MorphTo', 'MorphedByMany' => 'incoming',
            default => 'outgoing',
        };
    }

    /**
     * Get the cardinality of the relationship.
     */
    private function getCardinality(string $type): string
    {
        return match ($type) {
            'HasOne', 'MorphOne', 'HasOneThrough' => 'one-to-one',
            'HasMany', 'MorphMany', 'HasManyThrough' => 'one-to-many',
            'BelongsTo', 'MorphTo' => 'many-to-one',
            'BelongsToMany', 'MorphToMany', 'MorphedByMany' => 'many-to-many',
            default => 'unknown',
        };
    }
}
