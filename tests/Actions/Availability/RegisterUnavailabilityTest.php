<?php

namespace JustBetter\DynamicsClient\Tests\Actions\Availability;

use JustBetter\DynamicsClient\Actions\Availability\CheckAvailability;
use JustBetter\DynamicsClient\Actions\Availability\RegisterUnavailability;
use JustBetter\DynamicsClient\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RegisterUnavailabilityTest extends TestCase
{
    #[Test]
    public function it_keeps_track_of_count(): void
    {
        /** @var RegisterUnavailability $action */
        $action = app(RegisterUnavailability::class);

        $action->register('default');

        $this->assertEquals(1, cache()->get(RegisterUnavailability::COUNT_KEY.'default'));
    }

    #[Test]
    public function it_can_pass_threshold(): void
    {
        /** @var RegisterUnavailability $action */
        $action = app(RegisterUnavailability::class);

        cache()->put(RegisterUnavailability::COUNT_KEY.'default', 9);

        $action->register('default');

        $this->assertNull(cache()->get(RegisterUnavailability::COUNT_KEY.'default'));
        $this->assertFalse(cache()->get(CheckAvailability::AVAILABLE_KEY.'default'));
    }
}
