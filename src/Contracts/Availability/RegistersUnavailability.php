<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Contracts\Availability;

interface RegistersUnavailability
{
    public function register(string $connection): void;
}
