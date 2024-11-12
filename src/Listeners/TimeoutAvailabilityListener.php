<?php

namespace JustBetter\DynamicsClient\Listeners;

use JustBetter\DynamicsClient\Contracts\Availability\RegistersUnavailability;
use JustBetter\DynamicsClient\Events\DynamicsTimeoutEvent;

class TimeoutAvailabilityListener
{
    public function __construct(protected RegistersUnavailability $unavailability) {}

    public function handle(DynamicsTimeoutEvent $event): void
    {
        $this->unavailability->register($event->connection);
    }
}
