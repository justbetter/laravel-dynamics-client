<?php

namespace JustBetter\DynamicsClient\Listeners;

use JustBetter\DynamicsClient\Contracts\Availability\RegistersUnavailability;
use JustBetter\DynamicsClient\Events\DynamicsResponseEvent;

class ResponseAvailabilityListener
{
    public function __construct(protected RegistersUnavailability $unavailability)
    {
    }

    public function handle(DynamicsResponseEvent $event): void
    {
        /** @var array<int, int> $codes */
        $codes = config('dynamics.connections.'.$event->connection.'.availability.codes', [502, 503, 504]);

        if (! in_array($event->response->status(), $codes)) {
            return;
        }

        $this->unavailability->register($event->connection);
    }
}
