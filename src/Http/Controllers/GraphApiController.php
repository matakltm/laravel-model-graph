<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Matakltm\LaravelModelGraph\Services\GraphBuilderService;

/**
 * Class GraphApiController
 *
 * Handles the API routes for the model graph data.
 */
class GraphApiController extends Controller
{
    /**
     * GraphApiController constructor.
     */
    public function __construct(
        protected GraphBuilderService $graphBuilder
    ) {}

    /**
     * Get the model graph data as JSON.
     */
    public function data(): JsonResponse
    {
        /** @var array{enabled?: bool, key?: string, ttl?: int} $cacheConfig */
        $cacheConfig = Config::get('model-graph.cache', []);

        $enabled = $cacheConfig['enabled'] ?? true;
        $key = $cacheConfig['key'] ?? 'laravel-model-graph-data';
        $ttl = $cacheConfig['ttl'] ?? 3600;

        if ($enabled) {
            /** @var array<string, mixed> $data */
            $data = Cache::remember($key, $ttl, fn (): array => $this->graphBuilder->generate());
        } else {
            $data = $this->graphBuilder->generate();
        }

        return response()->json($data);
    }
}
