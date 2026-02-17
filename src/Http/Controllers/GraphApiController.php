<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Matakltm\LaravelModelGraph\Services\GraphBuilderService;

/**
 * Class GraphApiController
 *
 * Handles the API routes for the model graph.
 */
class GraphApiController extends Controller
{
    /**
     * GraphApiController constructor.
     */
    public function __construct(
        protected GraphBuilderService $builder
    ) {}

    /**
     * Get the model graph data.
     */
    public function data(): JsonResponse
    {
        return Response::json($this->builder->generate());
    }
}
