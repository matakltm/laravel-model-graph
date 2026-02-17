<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Matakltm\LaravelModelGraph\Services\GraphBuilderService;

/**
 * Class GraphApiController
 *
 * Serves the API data for the model graph.
 */
class GraphApiController extends Controller
{
    /**
     * Get the model graph data.
     */
    public function data(GraphBuilderService $builder): JsonResponse
    {
        /** @var bool $cacheEnabled */
        $cacheEnabled = Config::get('model-graph.cache.enabled', true);
        /** @var string $cacheKey */
        $cacheKey = Config::get('model-graph.cache.key', 'laravel-model-graph-data');
        /** @var int $cacheTtl */
        $cacheTtl = Config::get('model-graph.cache.ttl', 3600);

        if ($cacheEnabled && Cache::has($cacheKey)) {
            /** @var array<string, mixed> $data */
            $data = Cache::get($cacheKey);

            return response()->json($data);
        }

        /** @var string $storagePath */
        $storagePath = Config::get('model-graph.storage_path');

        if (! File::exists($storagePath)) {
            if (Config::get('model-graph.auto_generate', true)) {
                $data = $builder->generate();

                $directory = dirname($storagePath);
                if (! File::isDirectory($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }

                /** @var int $jsonOptions */
                $jsonOptions = Config::get('model-graph.json_options', JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                File::put($storagePath, (string) json_encode($data, $jsonOptions));
            } else {
                return response()->json([
                    'error' => 'Graph data not found. Please run php artisan model-graph:generate.',
                ], 404);
            }
        } else {
            /** @var string $content */
            $content = File::get($storagePath);
            /** @var array<string, mixed> $data */
            $data = json_decode($content, true);
        }

        if ($cacheEnabled) {
            Cache::put($cacheKey, $data, $cacheTtl);
        }

        return response()->json($data);
    }
}
