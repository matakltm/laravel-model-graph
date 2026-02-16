<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Services;

/**
 * Class RelationshipResolverService
 *
 * Resolves relationships between Eloquent models using reflection.
 */
class RelationshipResolverService
{
    /**
     * Resolve relationships for a given model.
     *
     * @return array<string, mixed>
     */
    public function resolve(string $model): array
    {
        return [];
    }
}
