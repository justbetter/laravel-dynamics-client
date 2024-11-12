<?php

namespace JustBetter\DynamicsClient\Tests\Listeners;

use Illuminate\Support\Facades\Http;
use JustBetter\DynamicsClient\Contracts\Availability\RegistersUnavailability;
use JustBetter\DynamicsClient\Exceptions\DynamicsException;
use JustBetter\DynamicsClient\OData\Pages\Item;
use JustBetter\DynamicsClient\Tests\TestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;

class ResponseAvailabilityListenerTest extends TestCase
{
    #[Test]
    public function it_does_not_trigger_on_ok_status(): void
    {
        Item::fake();

        $this->mock(RegistersUnavailability::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('register');
        });

        Http::fake([
            '*' => Http::response(null, 200),
        ])->preventStrayRequests();

        Item::query('default')->get();
    }

    #[Test]
    public function it_calls_action(): void
    {
        Item::fake();
        $this->mock(RegistersUnavailability::class, function (MockInterface $mock): void {
            $mock->shouldReceive('register')->with('default')->once();
        });

        Http::fake([
            '*' => Http::response(null, 503),
        ])->preventStrayRequests();

        $this->expectException(DynamicsException::class);
        Item::query('default')->get();
    }
}
