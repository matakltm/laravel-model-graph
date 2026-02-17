<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

/**
 * Class GraphController
 *
 * Serves the web view for the model graph SPA.
 */
class GraphController extends Controller
{
    /**
     * Render the model graph SPA.
     */
    public function index(): View
    {
        return view('model-graph::graph');
    }
}
