<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Events;

use Illuminate\Foundation\Events\Dispatchable;

class DynamicsTimeoutEvent
{
    use Dispatchable;

    public function __construct(
        public string $connection,
    ) {}
}
