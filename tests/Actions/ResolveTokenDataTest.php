<?php

namespace JustBetter\DynamicsClient\Tests\Actions;

use Illuminate\Support\Facades\Http;
use JustBetter\DynamicsClient\Actions\ResolveTokenData;
use JustBetter\DynamicsClient\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ResolveTokenDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'dynamics_redirect_uri' => Http::response([
                'token_type' => '::token-type::',
                'expires_in' => 3600,
                'ext_expires_in' => 3600,
                'access_token' => '::access-token::',
            ]),
        ])->preventStrayRequests();
    }

    #[Test]
    public function it_resolves_token(): void
    {
        /** @var ResolveTokenData $resolver */
        $resolver = app(ResolveTokenData::class);

        $token = $resolver->resolve([
            'client_id' => 'client_id',
            'client_secret' => 'client_secret',
            'redirect_uri' => 'dynamics_redirect_uri',
            'scope' => 'scope',
            'grant_type' => 'client_credentials',
        ]);

        $this->assertEquals('::access-token::', $token->accessToken());
        $this->assertEquals('::token-type::', $token->tokenType());
        $this->assertEquals(3600, $token->expiresIn());
        $this->assertEquals(3600, $token->extExpiresIn());
    }

    #[Test]
    public function it_resolves_from_cache(): void
    {
        $fakeTokenData = [
            'token_type' => '::token-type::',
            'expires_in' => 3600,
            'ext_expires_in' => 3600,
            'access_token' => '::cached-token::',
        ];

        cache()->rememberForever('dynamics-client:token', fn (): string => encrypt($fakeTokenData));

        /** @var ResolveTokenData $resolver */
        $resolver = app(ResolveTokenData::class);

        $token = $resolver->resolve([
            'client_id' => 'client_id',
            'client_secret' => 'client_secret',
            'redirect_uri' => 'dynamics_redirect_uri',
            'scope' => 'scope',
            'grant_type' => 'client_credentials',
        ]);

        $this->assertEquals('::cached-token::', $token->accessToken());
    }
}
