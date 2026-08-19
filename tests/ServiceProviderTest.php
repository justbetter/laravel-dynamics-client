<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use JustBetter\DynamicsClient\Actions\AuthenticateRequest;
use JustBetter\DynamicsClient\Actions\Availability\CheckAvailability;
use JustBetter\DynamicsClient\Actions\Availability\RegisterUnavailability;
use JustBetter\DynamicsClient\Actions\BuildRequest;
use JustBetter\DynamicsClient\Actions\OAuth\RequestAccessToken;
use JustBetter\DynamicsClient\Client\Dynamics;
use JustBetter\DynamicsClient\Contracts\AuthenticatesRequest;
use JustBetter\DynamicsClient\Contracts\Availability\ChecksAvailability;
use JustBetter\DynamicsClient\Contracts\Availability\RegistersUnavailability;
use JustBetter\DynamicsClient\Contracts\BuildsRequest;
use JustBetter\DynamicsClient\Contracts\OAuth\RequestsAccessToken;
use JustBetter\DynamicsClient\Events\DynamicsResponseEvent;
use JustBetter\DynamicsClient\Events\DynamicsTimeoutEvent;
use JustBetter\DynamicsClient\Listeners\ResponseAvailabilityListener;
use JustBetter\DynamicsClient\Listeners\TimeoutAvailabilityListener;
use PHPUnit\Framework\Attributes\Test;

final class ServiceProviderTest extends TestCase
{
    #[Test]
    public function it_merges_the_package_configuration(): void
    {
        $this->assertIsArray(config('dynamics.connections'));
    }

    #[Test]
    public function it_resolves_every_contract_to_its_implementation(): void
    {
        $this->assertInstanceOf(BuildRequest::class, app(BuildsRequest::class));
        $this->assertInstanceOf(AuthenticateRequest::class, app(AuthenticatesRequest::class));
        $this->assertInstanceOf(CheckAvailability::class, app(ChecksAvailability::class));
        $this->assertInstanceOf(RegisterUnavailability::class, app(RegistersUnavailability::class));
        $this->assertInstanceOf(RequestAccessToken::class, app(RequestsAccessToken::class));
    }

    #[Test]
    public function it_registers_the_request_actions_as_singletons(): void
    {
        $this->assertSame(app(BuildsRequest::class), app(BuildsRequest::class));
        $this->assertSame(app(AuthenticatesRequest::class), app(AuthenticatesRequest::class));
    }

    #[Test]
    public function it_resolves_a_fresh_client_for_every_call(): void
    {
        $this->assertNotSame(app(Dynamics::class), app(Dynamics::class));
    }

    #[Test]
    public function it_registers_the_availability_listeners(): void
    {
        $listeners = Event::getRawListeners();

        $this->assertContains(TimeoutAvailabilityListener::class, $listeners[DynamicsTimeoutEvent::class]);
        $this->assertContains(ResponseAvailabilityListener::class, $listeners[DynamicsResponseEvent::class]);
    }

    #[Test]
    public function it_registers_the_console_commands(): void
    {
        $this->assertArrayHasKey('dynamics:connect', Artisan::all());
    }
}
