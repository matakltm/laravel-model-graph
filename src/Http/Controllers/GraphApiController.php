<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Matakltm\LaravelModelGraph\Services\GraphBuilderService;

class GraphApiController extends Controller
{
    /**
     * Get the graph data in JSON format.
     */
    public function data(GraphBuilderService $builder): JsonResponse
    {
        /** @var array<string, mixed> $cacheConfig */
        $cacheConfig = Config::get('model-graph.cache', []);

        $cacheEnabled = (bool) ($cacheConfig['enabled'] ?? true);
        $cacheKey = (string) ($cacheConfig['key'] ?? 'laravel-model-graph-data');
        $cacheTtl = (int) ($cacheConfig['ttl'] ?? 3600);

        if ($cacheEnabled && Cache::has($cacheKey)) {
            /** @var array<string, mixed> $cachedData */
            $cachedData = Cache::get($cacheKey);
            return response()->json($cachedData);
        }

        $data = $builder->generate();

        if ($cacheEnabled) {
            Cache::put($cacheKey, $data, $cacheTtl);
        }

        return response()->json($data);
    }
}
