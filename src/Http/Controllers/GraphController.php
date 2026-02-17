<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

    /**
     * Serve package assets.
     */
    public function asset(string $file): BinaryFileResponse
    {
        $basePath = (string) realpath(__DIR__.'/../../../resources/dist');
        $fullPath = realpath($basePath.'/'.$file);

        if ($fullPath === false || ! str_starts_with($fullPath, $basePath) || ! is_file($fullPath)) {
            abort(404);
        }

        $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
        $mimeTypes = [
            'js' => 'application/javascript',
            'css' => 'text/css',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
        ];

        $headers = [
            'Cache-Control' => 'max-age=31536000, public',
        ];

        if (isset($mimeTypes[$extension])) {
            $headers['Content-Type'] = $mimeTypes[$extension];
        }

        return response()->file($fullPath, $headers);
    }
}
