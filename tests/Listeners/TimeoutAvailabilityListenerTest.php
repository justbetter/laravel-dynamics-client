<?php

namespace JustBetter\DynamicsClient\Tests\Listeners;

use JustBetter\DynamicsClient\Contracts\Availability\RegistersUnavailability;
use JustBetter\DynamicsClient\Events\DynamicsTimeoutEvent;
use JustBetter\DynamicsClient\Listeners\TimeoutAvailabilityListener;
use JustBetter\DynamicsClient\Tests\TestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;

class TimeoutAvailabilityListenerTest extends TestCase
{
    #[Test]
    public function it_calls_action(): void
    {
        $this->mock(RegistersUnavailability::class, function (MockInterface $mock): void {
            $mock->shouldReceive('register')->with('::connection::')->once();
        });

        /** @var TimeoutAvailabilityListener $listener */
        $listener = app(TimeoutAvailabilityListener::class);

        $listener->handle(new DynamicsTimeoutEvent('::connection::'));
    }
}
