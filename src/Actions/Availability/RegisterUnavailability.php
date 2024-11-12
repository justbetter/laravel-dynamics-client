<?php

namespace JustBetter\DynamicsClient\Actions\Availability;

use JustBetter\DynamicsClient\Contracts\Availability\RegistersUnavailability;

class RegisterUnavailability implements RegistersUnavailability
{
    public const COUNT_KEY = 'dynamics-client:unavailable-count:';

    public function register(string $connection): void
    {
        $countKey = static::COUNT_KEY.$connection;

        /** @var int $count */
        $count = cache()->get($countKey, 0);
        $count++;

        /** @var int $threshold */
        $threshold = config('dynamics.connections.'.$connection.'.availability.threshold', 10);

        /** @var int $timespan */
        $timespan = config('dynamics.connections.'.$connection.'.availability.timespan', 10);

        /** @var int $cooldown */
        $cooldown = config('dynamics.connections.'.$connection.'.availability.cooldown', 2);

        cache()->put($countKey, $count, now()->addMinutes($timespan));

        if ($count >= $threshold) {
            cache()->put(CheckAvailability::AVAILABLE_KEY.$connection, false, now()->addMinutes($cooldown));

            cache()->forget($countKey);
        }
    }

    public static function bind(): void
    {
        app()->singleton(RegistersUnavailability::class, static::class);
    }
}
