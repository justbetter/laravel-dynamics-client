<?php

namespace JustBetter\DynamicsClient\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Http\Client\Response;

class DynamicsResponseEvent
{
    use Dispatchable;

    public function __construct(
        public Response $response,
        public string $connection,
    ) {
    }
}
