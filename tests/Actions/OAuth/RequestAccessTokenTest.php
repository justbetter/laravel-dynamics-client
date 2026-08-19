<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Tests\Actions\OAuth;

use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use JustBetter\DynamicsClient\Client\Dynamics;
use JustBetter\DynamicsClient\Contracts\OAuth\RequestsAccessToken;
use JustBetter\DynamicsClient\Events\DynamicsResponseEvent;
use JustBetter\DynamicsClient\Events\DynamicsTimeoutEvent;
use JustBetter\DynamicsClient\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class RequestAccessTokenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('dynamics.connections.default', array_merge(
            config()->array('dynamics.connections.default'),
            [
                'parameters' => [
                    'tenant_id' => '::tenant-a::',
                    'environment' => '::environment::',
                    'api' => 'v2.0',
                    'company_id' => '::company-id::',
                ],
                'client_id' => '::client-id::',
                'client_secret' => '::client-secret::',
            ],
        ));
    }

    protected function fakeToken(string $accessToken = '::access-token::'): void
    {
        Http::fake([
            'https://login.microsoftonline.com/*/oauth2/v2.0/token' => Http::response([
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'access_token' => $accessToken,
            ]),
        ])->preventStrayRequests();
    }

    protected function tokenUrl(string $tenant): string
    {
        return 'https://login.microsoftonline.com/'.$tenant.'/oauth2/v2.0/token';
    }

    protected function cacheKey(string $tenant): string
    {
        return 'dynamics-client:token:default:'.sha1(implode('|', [
            $this->tokenUrl($tenant),
            'https://api.businesscentral.dynamics.com/.default',
            '::client-id::',
        ]));
    }

    #[Test]
    public function it_can_request_an_access_token(): void
    {
        $this->fakeToken();

        $token = app(RequestsAccessToken::class)->request(app(Dynamics::class));

        $this->assertSame([
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'access_token' => '::access-token::',
        ], $token->toArray());
        $this->assertSame('::access-token::', $token->accessToken());
        $this->assertSame('Bearer', $token->tokenType());
        $this->assertSame(3600, $token->expiresIn());

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === $this->tokenUrl('::tenant-a::')
            && $request->isForm()
            && $request->data() === [
                'client_id' => '::client-id::',
                'client_secret' => '::client-secret::',
                'scope' => 'https://api.businesscentral.dynamics.com/.default',
                'grant_type' => 'client_credentials',
            ]);
    }

    #[Test]
    public function it_caches_the_token_encrypted_until_shortly_before_it_expires(): void
    {
        $this->fakeToken();

        $token = app(RequestsAccessToken::class)->request(app(Dynamics::class));

        $cached = cache()->get($this->cacheKey('::tenant-a::'));

        $this->assertIsString($cached);
        $this->assertSame($token->toArray(), decrypt($cached));

        $this->travel(3539)->seconds();
        $this->assertNotNull(cache()->get($this->cacheKey('::tenant-a::')));

        $this->travel(2)->seconds();
        $this->assertNull(cache()->get($this->cacheKey('::tenant-a::')));
    }

    #[Test]
    public function it_can_return_a_cached_token_without_a_second_request(): void
    {
        Http::fake()->preventStrayRequests();

        cache()->forever($this->cacheKey('::tenant-a::'), encrypt([
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'access_token' => '::cached-token::',
        ]));

        $token = app(RequestsAccessToken::class)->request(app(Dynamics::class));

        $this->assertSame('::cached-token::', $token->accessToken());

        Http::assertNothingSent();
    }

    #[Test]
    public function it_can_throw_when_the_token_request_fails(): void
    {
        Http::fake([
            'https://login.microsoftonline.com/*' => Http::response(['error' => '::error-code::'], 401),
        ])->preventStrayRequests();

        $this->expectException(RequestException::class);

        app(RequestsAccessToken::class)->request(app(Dynamics::class));
    }

    #[Test]
    public function it_does_not_share_a_cached_token_between_tenants(): void
    {
        Http::fake([
            $this->tokenUrl('::tenant-a::') => Http::response([
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'access_token' => '::token-a::',
            ]),
            $this->tokenUrl('::tenant-b::') => Http::response([
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'access_token' => '::token-b::',
            ]),
        ])->preventStrayRequests();

        $action = app(RequestsAccessToken::class);

        $this->assertSame('::token-a::', $action->request(app(Dynamics::class)->tenantId('::tenant-a::'))->accessToken());
        $this->assertSame('::token-b::', $action->request(app(Dynamics::class)->tenantId('::tenant-b::'))->accessToken());

        Http::assertSentCount(2);

        $this->assertNotSame($this->cacheKey('::tenant-a::'), $this->cacheKey('::tenant-b::'));
        $this->assertNotNull(cache()->get($this->cacheKey('::tenant-a::')));
        $this->assertNotNull(cache()->get($this->cacheKey('::tenant-b::')));
    }

    #[Test]
    public function it_does_not_dispatch_availability_events(): void
    {
        Event::fake([DynamicsResponseEvent::class, DynamicsTimeoutEvent::class]);

        $this->fakeToken();

        app(RequestsAccessToken::class)->request(app(Dynamics::class));

        Event::assertNotDispatched(DynamicsResponseEvent::class);
        Event::assertNotDispatched(DynamicsTimeoutEvent::class);
    }
}
