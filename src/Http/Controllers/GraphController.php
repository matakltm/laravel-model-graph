<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Class GraphController
 *
 * Handles the web routes for the model graph SPA.
 */
class GraphController extends Controller
{
    /**
     * Render the model graph SPA view.
     */
    public function index(): View
    {
        return view('model-graph::graph');
    }

    /**
     * Serve a static asset from the package resources.
     */
    public function asset(string $file): BinaryFileResponse
    {
        $basePath = (string) realpath(__DIR__.'/../../../resources/dist');
        $path = realpath($basePath.'/'.$file);

        if ($path === false || ! str_starts_with($path, $basePath) || ! is_file($path)) {
            abort(404);
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $type = match ($extension) {
            'js' => 'text/javascript',
            'css' => 'text/css',
            'svg' => 'image/svg+xml',
            default => null,
        };

        $headers = [
            'Cache-Control' => 'public, max-age=31536000',
        ];

        if ($type !== null) {
            $headers['Content-Type'] = $type.'; charset=UTF-8';
        }

        return response()->file($path, $headers);
    }
}
