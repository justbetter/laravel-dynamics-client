<?php

namespace JustBetter\DynamicsClient\Actions\Availability;

use JustBetter\DynamicsClient\Contracts\Availability\ChecksAvailability;

class CheckAvailability implements ChecksAvailability
{
    const AVAILABLE_KEY = 'dynamics-client:availability:';

    public function check(string $connection): bool
    {
        return cache()->get(static::AVAILABLE_KEY.$connection, true);
    }

    public static function bind(): void
    {
        app()->singleton(ChecksAvailability::class, static::class);
    }
}
