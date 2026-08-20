<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Contracts\Availability;

interface ChecksAvailability
{
    public function check(string $connection): bool;
}
