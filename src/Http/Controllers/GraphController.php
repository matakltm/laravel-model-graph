<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

/**
 * Class GraphController
 *
 * Handles the web routes for the model graph.
 */
class GraphController extends Controller
{
    /**
     * Display the model graph.
     */
    public function index(): View
    {
        return view('model-graph::graph');
    }
}
