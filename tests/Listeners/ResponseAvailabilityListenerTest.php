<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Tests\Listeners;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\Client\Response;
use JustBetter\DynamicsClient\Contracts\Availability\RegistersUnavailability;
use JustBetter\DynamicsClient\Events\DynamicsResponseEvent;
use JustBetter\DynamicsClient\Listeners\ResponseAvailabilityListener;
use JustBetter\DynamicsClient\Tests\TestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;

final class ResponseAvailabilityListenerTest extends TestCase
{
    #[Test]
    public function it_does_not_trigger_on_ok_status(): void
    {
        $this->mock(RegistersUnavailability::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('register');
        });

        /** @var ResponseAvailabilityListener $listener */
        $listener = app(ResponseAvailabilityListener::class);

        $listener->handle(new DynamicsResponseEvent($this->response(200), 'default'));
    }

    #[Test]
    public function it_calls_action(): void
    {
        $this->mock(RegistersUnavailability::class, function (MockInterface $mock): void {
            $mock->shouldReceive('register')->with('default')->once();
        });

        /** @var ResponseAvailabilityListener $listener */
        $listener = app(ResponseAvailabilityListener::class);

        $listener->handle(new DynamicsResponseEvent($this->response(503), 'default'));
    }

    protected function response(int $status): Response
    {
        return new Response(new GuzzleResponse($status));
    }
}
