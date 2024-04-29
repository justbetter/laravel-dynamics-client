<?php

namespace JustBetter\DynamicsClient\Tests\Client;

use JustBetter\DynamicsClient\Actions\ResolveTokenData;
use JustBetter\DynamicsClient\Client\ClientFactory;
use JustBetter\DynamicsClient\Data\TokenData;
use JustBetter\DynamicsClient\Exceptions\DynamicsException;
use JustBetter\DynamicsClient\OData\BaseResource;
use JustBetter\DynamicsClient\Tests\TestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;

class ClientFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        BaseResource::fake();
    }

    #[Test]
    public function it_sets_oauth(): void
    {
        config()->set('dynamics.connections.default.auth', 'oauth');
        config()->set('dynamics.connections.default.oauth', [
            'client_id' => 'client_id',
            'client_secret' => 'client_secret',
            'redirect_uri' => 'redirect_url',
            'scope' => 'scope',
            'grant_type' => 'client_credentials',
        ]);

        $this->mock(ResolveTokenData::class, function (MockInterface $mock): void {
            $mock->shouldReceive('resolve')->andReturn(
                TokenData::of([
                    'token_type' => '::type::',
                    'expires_in' => 60,
                    'ext_expires_in' => 60,
                    'access_token' => '::access-token::',
                ])
            );
        });

        $factory = ClientFactory::make('default');

        $this->assertEquals('::type:: ::access-token::', data_get($factory->options, 'headers.Authorization'));
    }

    #[Test]
    public function it_sets_auth(): void
    {
        config()->set('dynamics.connections.default.auth', 'ntlm');

        $factory = ClientFactory::make('default');

        $this->assertEquals(['username', 'password', 'ntlm'], $factory->options['auth']);
    }

    #[Test]
    public function it_throws_exception_missing_config(): void
    {
        $this->expectException(DynamicsException::class);
        ClientFactory::make('does_not_exist');
    }

    #[Test]
    public function it_can_set_headers(): void
    {
        $factory = ClientFactory::make('default');
        $factory->headers(['headers']);

        $this->assertEquals(['headers'], $factory->options['headers']);
    }

    #[Test]
    public function it_can_set_etag(): void
    {
        $factory = ClientFactory::make('default');
        $factory->etag('etag');

        $this->assertEquals('etag', $factory->options['headers']['If-Match']);
    }
}
