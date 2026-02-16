<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ModelDiscovered
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param string $modelClass
     */
    public function __construct(public string $modelClass)
    {
    }
}
