<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Tests\Actions;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use JustBetter\DynamicsClient\Actions\BuildRequest;
use JustBetter\DynamicsClient\Client\Dynamics;
use JustBetter\DynamicsClient\Contracts\AuthenticatesRequest;
use JustBetter\DynamicsClient\Tests\TestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;

final class BuildRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('dynamics.connections.default', array_merge(
            config()->array('dynamics.connections.default'),
            [
                'parameters' => [
                    'tenant_id' => '::tenant-id::',
                    'environment' => '::environment::',
                    'api' => 'v2.0',
                    'company_id' => '::company-id::',
                ],
                'client_id' => '::client-id::',
                'client_secret' => '::client-secret::',
                'timeout' => 15,
                'connect_timeout' => 5,
            ],
        ));
    }

    protected function fakeAuthenticator(Dynamics $dynamics): void
    {
        $this->mock(AuthenticatesRequest::class, function (MockInterface $mock) use ($dynamics): void {
            $mock
                ->shouldReceive('authenticate')
                ->withArgs(fn (PendingRequest $request, Dynamics $client): bool => $client === $dynamics)
                ->once()
                ->andReturnArg(0);
        });
    }

    #[Test]
    public function it_can_build_a_request(): void
    {
        Http::fake();

        $dynamics = app(Dynamics::class);

        $this->fakeAuthenticator($dynamics);

        app(BuildRequest::class)->build($dynamics)->get('customers');

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'dynamics/customers'
                && $request->header('Accept') === ['application/json']
                && $request->header('Content-Type') === ['application/json'];
        });
    }

    #[Test]
    public function it_applies_the_configured_timeouts(): void
    {
        $dynamics = app(Dynamics::class);

        $this->fakeAuthenticator($dynamics);

        $request = app(BuildRequest::class)->build($dynamics);

        $this->assertSame(15, $request->getOptions()['timeout']);
        $this->assertSame(5, $request->getOptions()['connect_timeout']);
    }

    #[Test]
    public function it_applies_the_pending_headers_of_the_client(): void
    {
        Http::fake();

        $dynamics = app(Dynamics::class)->header('If-Match', '::etag::');

        $this->fakeAuthenticator($dynamics);

        app(BuildRequest::class)->build($dynamics)->patch('customers(::id::)', ['displayName' => '::name::']);

        Http::assertSent(fn (Request $request): bool => $request->header('If-Match') === ['::etag::']);
    }
}
