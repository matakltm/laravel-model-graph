<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

class GraphController extends Controller
{
    /**
     * Display the model graph SPA.
     */
    public function index(): View
    {
        return view('model-graph::graph');
    }
}
