<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Matakltm\LaravelModelGraph\Events\ModelDiscovered;
use Symfony\Component\Finder\Finder;

/**
 * Class ModelScannerService
 *
 * Scans the application for Eloquent models.
 */
class ModelScannerService
{
    /**
     * Scan the configured path for models.
     *
     * @return array<int, class-string<Model>>
     */
    public function scan(): array
    {
        /** @var string|null $cacheKeyConfig */
        $cacheKeyConfig = Config::get('model-graph.scan.cache_key');
        $cacheKey = $cacheKeyConfig ?? 'laravel-model-graph-models';

        /** @var bool|null $cacheEnabledConfig */
        $cacheEnabledConfig = Config::get('model-graph.cache.enabled');
        $cacheEnabled = $cacheEnabledConfig ?? true;

        if ($cacheEnabled && Cache::has($cacheKey)) {
            /** @var array<int, class-string<Model>> $cachedModels */
            $cachedModels = Cache::get($cacheKey);

            foreach ($cachedModels as $model) {
                event(new ModelDiscovered($model));
            }

            return $cachedModels;
        }

        /** @var string $path */
        $path = Config::get('model-graph.scan.models_path', app_path('Models'));

        if (! is_dir($path)) {
            return [];
        }

        $finder = new Finder;
        $finder->files()->in($path)->name('*.php');

        $models = [];
        /** @var array<int, string> $exclude */
        $exclude = Config::get('model-graph.scan.exclude', []);
        /** @var array<int, string> $includeOnly */
        $includeOnly = Config::get('model-graph.scan.include_only', []);

        foreach ($finder as $file) {
            $fullClassName = $this->getClassFromFile($file->getRealPath());

            if ($fullClassName === null) {
                continue;
            }

            if (! class_exists($fullClassName)) {
                continue;
            }

            if (! is_subclass_of($fullClassName, Model::class)) {
                continue;
            }

            /** @var class-string<Model> $fullClassName */
            if (! empty($includeOnly) && ! in_array($fullClassName, $includeOnly)) {
                continue;
            }

            if (in_array($fullClassName, $exclude)) {
                continue;
            }

            $models[] = $fullClassName;
            event(new ModelDiscovered($fullClassName));
        }

        if ($cacheEnabled) {
            /** @var int $ttl */
            $ttl = Config::get('model-graph.cache.ttl', 3600);
            Cache::put($cacheKey, $models, $ttl);
        }

        return $models;
    }

    /**
     * Get the full class name from a file path.
     */
    protected function getClassFromFile(string $path): ?string
    {
        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }

        $namespace = $this->extractNamespace($content);
        $className = $this->extractClassName($content);

        if ($className === null) {
            return null;
        }

        return $namespace !== null ? $namespace.'\\'.$className : $className;
    }

    /**
     * Extract the namespace from the file content.
     */
    private function extractNamespace(string $content): ?string
    {
        if (preg_match('/namespace\s+(.+?);/s', $content, $matches) === 1) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Extract the class name from the file content.
     */
    private function extractClassName(string $content): ?string
    {
        if (preg_match('/class\s+(\w+)/', $content, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
