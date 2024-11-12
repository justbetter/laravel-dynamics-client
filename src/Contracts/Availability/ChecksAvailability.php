<?php

namespace JustBetter\DynamicsClient\Contracts\Availability;

interface ChecksAvailability
{
    public function check(string $connection): bool;
}
