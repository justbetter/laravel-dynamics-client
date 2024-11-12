<?php

namespace JustBetter\DynamicsClient\Contracts\Availability;

interface RegistersUnavailability
{
    public function register(string $connection): void;
}
