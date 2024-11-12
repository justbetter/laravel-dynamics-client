<?php

namespace JustBetter\DynamicsClient\Tests\Client;

use JustBetter\DynamicsClient\Client\ClientHttpProvider;
use JustBetter\DynamicsClient\Contracts\Availability\ChecksAvailability;
use JustBetter\DynamicsClient\Exceptions\UnavailableException;
use JustBetter\DynamicsClient\Tests\TestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use SaintSystems\OData\HttpRequestMessage;

class ClientHttpProviderTest extends TestCase
{
    #[Test]
    public function it_throws_when_unavailable(): void
    {
        $this->mock(ChecksAvailability::class, function(MockInterface $mock): void {
           $mock->shouldReceive('check')->with('default')->andReturnFalse();
        });

        config()->set('dynamics.connections.default.availability.throw', true);
        $provider = new ClientHttpProvider('default');

        $this->expectException(UnavailableException::class);

        $provider->send(new HttpRequestMessage('GET', 'http://example.com'));
    }
}
