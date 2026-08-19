<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Tests\Actions;

use Illuminate\Support\Facades\Http;
use JustBetter\DynamicsClient\Actions\AuthenticateRequest;
use JustBetter\DynamicsClient\Client\Dynamics;
use JustBetter\DynamicsClient\Contracts\OAuth\RequestsAccessToken;
use JustBetter\DynamicsClient\Data\TokenData;
use JustBetter\DynamicsClient\Tests\TestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;

final class AuthenticateRequestTest extends TestCase
{
    #[Test]
    public function it_can_authenticate_a_request_with_the_resolved_token(): void
    {
        $dynamics = app(Dynamics::class);

        $this->mock(RequestsAccessToken::class, function (MockInterface $mock) use ($dynamics): void {
            $mock
                ->shouldReceive('request')
                ->with($dynamics)
                ->once()
                ->andReturn(TokenData::make([
                    'token_type' => 'Bearer',
                    'expires_in' => 3600,
                    'access_token' => '::access-token::',
                ]));
        });

        $request = app(AuthenticateRequest::class)->authenticate(
            Http::baseUrl('https://api.businesscentral.dynamics.com'),
            $dynamics
        );

        $this->assertSame('Bearer ::access-token::', data_get($request->getOptions(), 'headers.Authorization'));
    }
}
