<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Services;

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
        return [];
    }
}
