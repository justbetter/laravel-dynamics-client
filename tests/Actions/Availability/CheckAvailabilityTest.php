<?php

namespace JustBetter\DynamicsClient\Tests\Actions\Availability;

use JustBetter\DynamicsClient\Actions\Availability\CheckAvailability;
use JustBetter\DynamicsClient\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CheckAvailabilityTest extends TestCase
{
    #[Test]
    public function it_checks_availability(): void
    {
        /** @var CheckAvailability $action */
        $action = app(CheckAvailability::class);

        $this->assertTrue($action->check('default'));

        cache()->put(CheckAvailability::AVAILABLE_KEY.'default', false);

        $this->assertFalse($action->check('default'));
    }
}
