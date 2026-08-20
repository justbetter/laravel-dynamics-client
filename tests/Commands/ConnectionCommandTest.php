<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Tests\Commands;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\PendingCommand;
use JustBetter\DynamicsClient\Commands\ConnectionCommand;
use JustBetter\DynamicsClient\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class ConnectionCommandTest extends TestCase
{
    protected function fakeToken(): void
    {
        Http::fake([
            'https://login.microsoftonline.com/::tenant-id::/oauth2/v2.0/token' => Http::response([
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'access_token' => '::access-token::',
            ]),
        ]);
    }

    #[Test]
    public function it_reports_the_company_name(): void
    {
        $this->fakeToken();

        Http::fake([
            'dynamics/' => Http::response(['name' => '::company-name::']),
        ])->preventStrayRequests();

        /** @var PendingCommand $command */
        $command = $this->artisan(ConnectionCommand::class);

        $command
            ->assertSuccessful()
            ->expectsOutput('Successfully connected to company "::company-name::"')
            ->run();

        Http::assertSent(fn ($request): bool => $request->url() === 'dynamics/');
    }

    #[Test]
    public function it_checks_the_connection_given_as_an_argument(): void
    {
        config()->set('dynamics.connections.secondary', array_merge(
            config()->array('dynamics.connections.default'),
            ['parameters' => ['tenant_id' => '::other-tenant::', 'environment' => '::environment::', 'api' => 'v2.0', 'company_id' => '::company-id::']],
        ));

        Http::fake([
            'https://login.microsoftonline.com/::other-tenant::/oauth2/v2.0/token' => Http::response([
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'access_token' => '::access-token::',
            ]),
            '*' => Http::response(['name' => '::other-company::']),
        ])->preventStrayRequests();

        /** @var PendingCommand $command */
        $command = $this->artisan(ConnectionCommand::class, ['connection' => 'secondary']);

        $command
            ->assertSuccessful()
            ->expectsOutput('Successfully connected to company "::other-company::"')
            ->run();
    }

    #[Test]
    public function it_fails_on_an_unknown_connection(): void
    {
        Http::fake()->preventStrayRequests();

        /** @var PendingCommand $command */
        $command = $this->artisan(ConnectionCommand::class, ['connection' => '::unknown::']);

        $command
            ->assertFailed()
            ->expectsOutput('Could not connect to "::unknown::": Connection "::unknown::" not found in the configuration file.')
            ->run();

        Http::assertNothingSent();
    }

    #[Test]
    public function it_fails_when_the_token_request_fails(): void
    {
        // Earlier-registered Http fakes win, so the token success stub from Dynamics::fake() must be reset first.
        app()->forgetInstance(HttpFactory::class);
        Http::clearResolvedInstance(HttpFactory::class);

        Http::fake([
            'https://login.microsoftonline.com/::tenant-id::/oauth2/v2.0/token' => Http::response(['error' => '::error-code::'], 401),
        ])->preventStrayRequests();

        /** @var PendingCommand $command */
        $command = $this->artisan(ConnectionCommand::class);

        $command
            ->assertFailed()
            ->expectsOutputToContain('Could not connect to "default": HTTP request returned status code 401')
            ->run();
    }

    #[Test]
    public function it_fails_when_the_company_request_fails(): void
    {
        $this->fakeToken();

        Http::fake([
            'dynamics/' => Http::response(['error' => '::error-code::'], 404),
        ])->preventStrayRequests();

        /** @var PendingCommand $command */
        $command = $this->artisan(ConnectionCommand::class);

        $command
            ->assertFailed()
            ->expectsOutputToContain('Could not connect to "default": HTTP request returned status code 404')
            ->run();
    }
}
