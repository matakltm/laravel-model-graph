<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Services;

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

        $graph = [
            'models' => [],
            'relationships' => [],
        ];

        foreach ($models as $model) {
            $graph['models'][] = $this->inspector->inspect($model);
            $graph['relationships'] = array_merge($graph['relationships'], $this->resolver->resolve($model));

            if ($onProgress !== null) {
                $onProgress($model);
            }
        }

        return $graph;
    }
}
