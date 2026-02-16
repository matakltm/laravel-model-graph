<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Finder\Finder;

/**
 * Class ModelScannerService
 *
 * Scans the application for Eloquent models.
 */
class ModelScannerService
{
    /**
     * Scan the configured path for models.
     *
     * @return array<int, string>
     */
    public function scan(): array
    {
        /** @var string $path */
        $path = Config::get('model-graph.scan.models_path', app_path('Models'));

        if (! is_dir($path)) {
            return [];
        }

        $finder = new Finder();
        $finder->files()->in($path)->name('*.php');

        $models = [];

        foreach ($finder as $file) {
            $content = $file->getContents();
            $namespace = $this->extractNamespace($content);
            $className = $this->extractClassName($content);

            if ($namespace !== null && $className !== null) {
                $fullClassName = $namespace . '\\' . $className;

                if (class_exists($fullClassName) && is_subclass_of($fullClassName, Model::class)) {
                    $models[] = $fullClassName;
                }
            }
        }

        return $models;
    }

    /**
     * Extract the namespace from the file content.
     */
    private function extractNamespace(string $content): ?string
    {
        if (preg_match('/namespace\s+(.+?);/s', $content, $matches) === 1) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Extract the class name from the file content.
     */
    private function extractClassName(string $content): ?string
    {
        if (preg_match('/class\s+(\w+)/', $content, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
