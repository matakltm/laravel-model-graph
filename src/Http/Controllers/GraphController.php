<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class GraphController extends Controller
{
    /**
     * Display the model graph SPA.
     */
    public function index(): View
    {
        /** @var View $view */
        $view = view('model-graph::graph');

        return $view;
    }
}
