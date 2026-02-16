<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Matakltm\LaravelModelGraph\Events\ModelDiscovered;
use ReflectionClass;
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
     * @return array<int, string>
     */
    public function scan(): array
    {
        $cacheDuration = (int) Config::get('model-graph.cache_duration', 3600);
        $cacheKey = 'laravel-model-graph-models';

        if ($cacheDuration > 0 && Cache::has($cacheKey)) {
            /** @var array<int, string> $models */
            $models = (array) Cache::get($cacheKey);

            foreach ($models as $model) {
                ModelDiscovered::dispatch($model);
            }

            return $models;
        }

        $models = $this->discoverModels();

        if ($cacheDuration > 0) {
            Cache::put($cacheKey, $models, $cacheDuration);
        }

        return $models;
    }

    /**
     * Discover all Eloquent models in the configured paths.
     *
     * @return array<int, string>
     */
    protected function discoverModels(): array
    {
        /** @var array<int, string> $paths */
        $paths = Config::get('model-graph.scan.models_paths', [app_path('Models')]);
        /** @var array<int, string> $ignoreModels */
        $ignoreModels = Config::get('model-graph.scan.ignore_models', []);
        /** @var array<int, string> $includeOnly */
        $includeOnly = Config::get('model-graph.scan.include_only', []);

        $models = [];

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $finder = new Finder;
            $finder->files()->in($path)->name('*.php');

            foreach ($finder as $file) {
                $class = $this->getClassFromFile($file->getRealPath());

                if ($class && $this->isEloquentModel($class)) {
                    if (! empty($includeOnly) && ! in_array($class, $includeOnly)) {
                        continue;
                    }

                    if (in_array($class, $ignoreModels)) {
                        continue;
                    }

                    $models[] = $class;
                    ModelDiscovered::dispatch($class);
                }
            }
        }

        return array_unique($models);
    }

    /**
     * Get the class name from the file path.
     */
    protected function getClassFromFile(string $path): ?string
    {
        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }

        $tokens = token_get_all($content);
        $namespace = '';
        $class = '';

        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            if ($tokens[$i][0] === T_NAMESPACE) {
                for ($j = $i + 1; $j < $count; $j++) {
                    if (is_array($tokens[$j])) {
                        if ($tokens[$j][0] === T_STRING || (defined('T_NAME_QUALIFIED') && $tokens[$j][0] === T_NAME_QUALIFIED)) {
                            $namespace .= $tokens[$j][1];
                        }
                    } elseif ($tokens[$j] === '{' || $tokens[$j] === ';') {
                        break;
                    }
                }
            }

            if ($tokens[$i][0] === T_CLASS) {
                for ($j = $i + 1; $j < $count; $j++) {
                    if ($tokens[$j] === '{') {
                        break;
                    }
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                        $class = $tokens[$j][1];
                        break;
                    }
                }
            }

            if ($class) {
                return $namespace ? $namespace.'\\'.$class : $class;
            }
        }

        return null;
    }

    /**
     * Check if the class is an Eloquent model.
     */
    protected function isEloquentModel(string $class): bool
    {
        if (! class_exists($class)) {
            return false;
        }

        $reflection = new ReflectionClass($class);

        return $reflection->isSubclassOf(Model::class) && ! $reflection->isAbstract();
    }
}
