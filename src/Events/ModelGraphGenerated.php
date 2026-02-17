<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class ModelGraphGenerated
 *
 * Fired after the model graph JSON data has been generated.
 */
class ModelGraphGenerated
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param array<string, mixed> $data The generated graph data.
     */
    public function __construct(public array $data) {}
}
