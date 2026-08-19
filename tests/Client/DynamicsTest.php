<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Tests\Client;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use JustBetter\DynamicsClient\Actions\Availability\CheckAvailability;
use JustBetter\DynamicsClient\Client\Dynamics;
use JustBetter\DynamicsClient\Contracts\AuthenticatesRequest;
use JustBetter\DynamicsClient\Contracts\Availability\ChecksAvailability;
use JustBetter\DynamicsClient\Data\Entity;
use JustBetter\DynamicsClient\Events\DynamicsResponseEvent;
use JustBetter\DynamicsClient\Events\DynamicsTimeoutEvent;
use JustBetter\DynamicsClient\Exceptions\DynamicsException;
use JustBetter\DynamicsClient\Exceptions\UnavailableException;
use JustBetter\DynamicsClient\Tests\TestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;

final class DynamicsTest extends TestCase
{
    /** @param array<string, mixed> $overrides */
    protected function setConnection(array $overrides = []): void
    {
        config()->set('dynamics.connections.default', array_merge(
            config()->array('dynamics.connections.default'),
            [
                'parameters' => [
                    'tenant_id' => '::tenant-id::',
                    'environment' => '::environment::',
                    'api' => 'v2.0',
                    'company_id' => '::company-id::',
                ],
                'companies' => [
                    'acme' => '::acme-company-id::',
                ],
                'client_id' => '::client-id::',
                'client_secret' => '::client-secret::',
            ],
            $overrides,
        ));
    }

    public const string BASE_URL = 'https://api.businesscentral.dynamics.com/v2.0/::tenant-id::/::environment::/api/v2.0/companies(::company-id::)';

    protected function fakeAuthentication(): void
    {
        $this->mock(AuthenticatesRequest::class, function (MockInterface $mock): void {
            $mock
                ->shouldReceive('authenticate')
                ->atLeast()
                ->once()
                ->andReturnArg(0);
        });
    }

    /** @param array<string, mixed> $attributes */
    protected function customer(array $attributes = []): Entity
    {
        return Entity::from(array_merge([
            'id' => '::id::',
            '@odata.etag' => 'W/"::etag::"',
            'displayName' => '::name::',
        ], $attributes), ['endpoint' => 'customers']);
    }

    #[Test]
    public function it_can_throw_an_exception_for_an_unknown_connection(): void
    {
        $this->expectException(DynamicsException::class);
        $this->expectExceptionMessage('Connection "::connection::" not found in the configuration file.');

        Dynamics::config('::connection::');
    }

    #[Test]
    public function it_can_throw_an_exception_for_an_incomplete_connection(): void
    {
        config()->set('dynamics.connections.default', [
            'parameters' => [
                'tenant_id' => '::tenant-id::',
            ],
            'client_id' => '::client-id::',
        ]);

        $this->expectException(ValidationException::class);

        Dynamics::config('default');
    }

    #[Test]
    public function it_can_switch_connection(): void
    {
        $dynamics = app(Dynamics::class);

        $this->assertSame('default', $dynamics->getConnection());
        $this->assertSame('::connection::', $dynamics->connection('::connection::')->getConnection());
    }

    #[Test]
    public function it_can_override_a_single_parameter(): void
    {
        $this->setConnection();

        $dynamics = app(Dynamics::class)->set('environment', '::other-environment::');

        $this->assertSame('::other-environment::', $dynamics->parameter('environment'));
        $this->assertSame([
            'tenant_id' => '::tenant-id::',
            'environment' => '::other-environment::',
            'api' => 'v2.0',
            'company_id' => '::company-id::',
        ], $dynamics->getParameters());
    }

    #[Test]
    public function it_can_merge_an_array_of_parameters(): void
    {
        $this->setConnection();

        $dynamics = app(Dynamics::class)
            ->set('environment', '::other-environment::')
            ->parameters([
                'api' => '::other-api::',
                'custom' => '::custom::',
            ]);

        $this->assertSame('::other-environment::', $dynamics->parameter('environment'));
        $this->assertSame('::other-api::', $dynamics->parameter('api'));
        $this->assertSame('::custom::', $dynamics->parameter('custom'));
    }

    #[Test]
    public function it_can_override_parameters_with_typed_helpers(): void
    {
        $this->setConnection();

        $dynamics = app(Dynamics::class)
            ->tenantId('::other-tenant-id::')
            ->environment('::other-environment::')
            ->api('::other-api::')
            ->companyId('::other-company-id::');

        $this->assertSame([
            'tenant_id' => '::other-tenant-id::',
            'environment' => '::other-environment::',
            'api' => '::other-api::',
            'company_id' => '::other-company-id::',
        ], $dynamics->getParameters());
    }

    #[Test]
    public function it_can_select_a_company_by_name(): void
    {
        $this->setConnection();

        $dynamics = app(Dynamics::class)->company('acme');

        $this->assertSame('::acme-company-id::', $dynamics->parameter('company_id'));
    }

    #[Test]
    public function it_can_return_null_for_an_unresolved_parameter(): void
    {
        $this->setConnection(['parameters' => ['tenant_id' => '']]);

        $dynamics = app(Dynamics::class);

        $this->assertNull($dynamics->parameter('tenant_id'));
        $this->assertNull($dynamics->parameter('::unknown::'));
    }

    #[Test]
    public function it_can_set_headers_for_the_next_request(): void
    {
        $this->setConnection();

        $dynamics = app(Dynamics::class)
            ->header('If-Match', '::etag::')
            ->headers(['Prefer' => 'return=representation']);

        $this->assertSame([
            'If-Match' => '::etag::',
            'Prefer' => 'return=representation',
        ], $dynamics->getHeaders());
    }

    #[Test]
    public function it_can_clear_headers_while_keeping_parameter_overrides(): void
    {
        $this->setConnection();

        $dynamics = app(Dynamics::class)
            ->environment('::other-environment::')
            ->header('If-Match', '::etag::')
            ->clearHeaders();

        $this->assertSame([], $dynamics->getHeaders());
        $this->assertSame('::other-environment::', $dynamics->parameter('environment'));
    }

    #[Test]
    public function it_can_reset_overrides_and_headers(): void
    {
        $this->setConnection();

        $dynamics = app(Dynamics::class)
            ->environment('::other-environment::')
            ->header('If-Match', '::etag::')
            ->entity($this->customer())
            ->reset();

        $this->assertSame([], $dynamics->getHeaders());
        $this->assertNotInstanceOf(Entity::class, $dynamics->getEntity());
        $this->assertSame('::environment::', $dynamics->parameter('environment'));
    }

    #[Test]
    public function it_can_build_the_base_url_and_token_url(): void
    {
        $this->setConnection();

        $dynamics = app(Dynamics::class)
            ->set('environment', '::other-environment::')
            ->company('acme')
            ->tenantId('::other-tenant-id::');

        $this->assertSame(
            'https://api.businesscentral.dynamics.com/v2.0/::other-tenant-id::/::other-environment::/api/v2.0/companies(::acme-company-id::)',
            $dynamics->baseUrl(),
        );
        $this->assertSame(
            'https://login.microsoftonline.com/::other-tenant-id::/oauth2/v2.0/token',
            $dynamics->tokenUrl(),
        );
    }

    #[Test]
    public function it_can_throw_an_exception_for_a_missing_parameter(): void
    {
        $this->setConnection([
            'parameters' => [
                'tenant_id' => '::tenant-id::',
                'environment' => '::environment::',
                'api' => 'v2.0',
            ],
        ]);

        $this->expectException(DynamicsException::class);
        $this->expectExceptionMessage('Parameter "company_id" has no value to resolve placeholder "{company_id}".');

        app(Dynamics::class)->baseUrl();
    }

    #[Test]
    public function it_can_throw_an_exception_for_an_empty_parameter(): void
    {
        $this->setConnection([
            'parameters' => [
                'tenant_id' => '',
                'environment' => '::environment::',
                'api' => 'v2.0',
                'company_id' => '::company-id::',
            ],
        ]);

        $this->expectException(DynamicsException::class);
        $this->expectExceptionMessage('Parameter "tenant_id" has no value to resolve placeholder "{tenant_id}".');

        app(Dynamics::class)->tokenUrl();
    }

    #[Test]
    public function it_can_trim_a_leading_slash_from_a_path(): void
    {
        $this->setConnection();

        $this->assertSame('customers', app(Dynamics::class)->getUrl('/customers'));
    }

    #[Test]
    public function it_can_perform_a_get_request(): void
    {
        $this->setConnection();
        Event::fake([DynamicsResponseEvent::class]);
        Http::fake();
        $this->fakeAuthentication();

        $response = app(Dynamics::class)->get('/customers', ['$top' => 10]);

        $this->assertTrue($response->successful());

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === self::BASE_URL.'/customers?%24top=10');

        Event::assertDispatched(
            DynamicsResponseEvent::class,
            fn (DynamicsResponseEvent $event): bool => $event->connection === 'default'
                && $event->response === $response
        );
    }

    #[Test]
    public function it_can_perform_a_post_request(): void
    {
        $this->setConnection();
        Event::fake([DynamicsResponseEvent::class]);
        Http::fake();
        $this->fakeAuthentication();

        app(Dynamics::class)->post('customers', ['displayName' => '::name::']);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === self::BASE_URL.'/customers'
            && $request->data() === ['displayName' => '::name::']);

        Event::assertDispatched(DynamicsResponseEvent::class);
    }

    #[Test]
    public function it_can_perform_a_patch_request(): void
    {
        $this->setConnection();
        Event::fake([DynamicsResponseEvent::class]);
        Http::fake();
        $this->fakeAuthentication();

        app(Dynamics::class)->patch('customers(::id::)', ['displayName' => '::name::']);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PATCH'
            && $request->url() === self::BASE_URL.'/customers(::id::)'
            && $request->data() === ['displayName' => '::name::']);

        Event::assertDispatched(DynamicsResponseEvent::class);
    }

    #[Test]
    public function it_can_perform_a_put_request(): void
    {
        $this->setConnection();
        Event::fake([DynamicsResponseEvent::class]);
        Http::fake();
        $this->fakeAuthentication();

        app(Dynamics::class)->put('customers(::id::)/picture/content', ['content' => '::content::']);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && $request->url() === self::BASE_URL.'/customers(::id::)/picture/content'
            && $request->data() === ['content' => '::content::']);

        Event::assertDispatched(DynamicsResponseEvent::class);
    }

    #[Test]
    public function it_can_perform_a_delete_request(): void
    {
        $this->setConnection();
        Event::fake([DynamicsResponseEvent::class]);
        Http::fake();
        $this->fakeAuthentication();

        app(Dynamics::class)->delete('customers(::id::)');

        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && $request->url() === self::BASE_URL.'/customers(::id::)'
            && $request->data() === []);

        Event::assertDispatched(DynamicsResponseEvent::class);
    }

    #[Test]
    public function it_can_perform_a_base_level_request_without_a_trailing_slash(): void
    {
        $this->setConnection();
        Http::fake();
        $this->fakeAuthentication();

        app(Dynamics::class)->get('');

        Http::assertSent(fn (Request $request): bool => $request->url() === self::BASE_URL);
    }

    #[Test]
    public function it_can_keep_odata_query_parameters_intact(): void
    {
        $this->setConnection();
        Http::fake();
        $this->fakeAuthentication();

        $filter = "displayName eq '::name-a & name-b::'";

        app(Dynamics::class)->get('customers', [
            '$filter' => $filter,
            '$select' => 'id,displayName',
        ]);

        Http::assertSent(function (Request $request) use ($filter): bool {
            $this->assertSame($filter, $request->data()['$filter']);
            $this->assertSame('id,displayName', $request->data()['$select']);

            return $request->url() === self::BASE_URL.'/customers'
                .'?%24filter=displayName%20eq%20%27%3A%3Aname-a%20%26%20name-b%3A%3A%27&%24select=id%2CdisplayName';
        });
    }

    #[Test]
    public function it_can_return_a_failed_response_to_the_caller(): void
    {
        $this->setConnection();
        Event::fake([DynamicsResponseEvent::class]);
        Http::fake(['*' => Http::response(['error' => ['code' => '::error-code::']], 404)]);
        $this->fakeAuthentication();

        $response = app(Dynamics::class)->get('customers(::unknown::)');

        $this->assertTrue($response->failed());
        $this->assertSame(404, $response->status());
        $this->assertSame('::error-code::', $response->json('error.code'));

        Event::assertDispatched(DynamicsResponseEvent::class);
    }

    #[Test]
    public function it_can_dispatch_a_timeout_event_and_rethrow_the_exception(): void
    {
        $this->setConnection();
        Event::fake([DynamicsResponseEvent::class, DynamicsTimeoutEvent::class]);
        Http::fake(fn (): never => throw new ConnectionException('::message::'));
        $this->fakeAuthentication();

        try {
            app(Dynamics::class)->connection('default')->get('customers');

            $this->fail('The connection exception should have been rethrown.');
        } catch (ConnectionException $connectionException) {
            $this->assertSame('::message::', $connectionException->getMessage());
        }

        Event::assertDispatched(
            DynamicsTimeoutEvent::class,
            fn (DynamicsTimeoutEvent $event): bool => $event->connection === 'default'
        );
        Event::assertNotDispatched(DynamicsResponseEvent::class);
    }

    #[Test]
    public function it_can_clear_headers_once_the_request_completed(): void
    {
        $this->setConnection();
        Http::fake();
        $this->fakeAuthentication();

        $dynamics = app(Dynamics::class);

        $dynamics->header('If-Match', '::etag::')->patch('customers(::id::)', ['displayName' => '::name::']);

        $this->assertSame([], $dynamics->getHeaders());

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('If-Match', '::etag::'));
    }

    #[Test]
    public function it_can_map_a_list_response_onto_entities(): void
    {
        $this->setConnection();
        Http::fake(['*' => Http::response([
            'value' => [
                ['id' => '::first::', 'displayName' => '::first-name::'],
                ['id' => '::second::', 'displayName' => '::second-name::'],
            ],
        ])]);
        $this->fakeAuthentication();

        $entities = app(Dynamics::class)->entities('customers', ['$top' => 2]);

        $this->assertCount(2, $entities);
        $this->assertSame('::first::', $entities->first()?->id());
        $this->assertSame('customers', $entities->first()->endpoint());
        $this->assertSame('customers(::second::)', $entities->last()?->url());

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && $request->url() === self::BASE_URL.'/customers?%24top=2');
    }

    #[Test]
    public function it_can_lazily_map_entities_page_by_page(): void
    {
        $this->setConnection(['page_size' => 2]);
        Http::fake(['*' => Http::sequence()
            ->push(['value' => [['id' => '::first::'], ['id' => '::second::']]])
            ->push(['value' => [['id' => '::third::']]]),
        ]);
        $this->fakeAuthentication();

        $entities = app(Dynamics::class)->lazyEntities('customers')->collect();

        $this->assertSame(
            ['::first::', '::second::', '::third::'],
            $entities->map(fn (Entity $entity): ?string => $entity->id())->all(),
        );
        $this->assertSame('customers', $entities->first()?->endpoint());

        Http::assertSent(fn (Request $request): bool => $request->data() === ['$top' => 2, '$skip' => 0]);
        Http::assertSent(fn (Request $request): bool => $request->data() === ['$top' => 2, '$skip' => 2]);
    }

    #[Test]
    public function it_can_lazily_map_entities_with_an_explicit_page_size(): void
    {
        $this->setConnection(['page_size' => 500]);
        Http::fake(['*' => Http::response(['value' => []])]);
        $this->fakeAuthentication();

        $entities = app(Dynamics::class)->lazyEntities('customers', ['$orderby' => 'id'], 50);

        $this->assertCount(0, $entities);

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->data() === [
            '$orderby' => 'id',
            '$top' => 50,
            '$skip' => 0,
        ]);
    }

    #[Test]
    public function it_can_lazily_iterate_a_single_page(): void
    {
        $this->setConnection(['page_size' => 2]);
        Http::fake(['*' => Http::response(['value' => [['id' => '::only::']]])]);
        $this->fakeAuthentication();

        $records = app(Dynamics::class)->lazy('customers')->collect();

        $this->assertSame([['id' => '::only::']], $records->all());

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->data() === ['$top' => 2, '$skip' => 0]);
    }

    #[Test]
    public function it_can_lazily_iterate_multiple_pages(): void
    {
        $this->setConnection(['page_size' => 500]);
        Http::fake(['*' => Http::sequence()
            ->push(['value' => [['id' => '::first::'], ['id' => '::second::']]])
            ->push(['value' => [['id' => '::third::'], ['id' => '::fourth::']]])
            ->push(['value' => [['id' => '::fifth::']]]),
        ]);
        $this->fakeAuthentication();

        $records = app(Dynamics::class)->lazy('customers', ['$orderby' => 'id'], 2)->collect();

        $this->assertSame(
            ['::first::', '::second::', '::third::', '::fourth::', '::fifth::'],
            $records->pluck('id')->all(),
        );

        Http::assertSentCount(3);
        Http::assertSent(fn (Request $request): bool => $request->data() === ['$orderby' => 'id', '$top' => 2, '$skip' => 0]);
        Http::assertSent(fn (Request $request): bool => $request->data() === ['$orderby' => 'id', '$top' => 2, '$skip' => 2]);
        Http::assertSent(fn (Request $request): bool => $request->data() === ['$orderby' => 'id', '$top' => 2, '$skip' => 4]);
    }

    #[Test]
    public function it_stops_lazily_iterating_on_an_empty_first_page(): void
    {
        $this->setConnection();
        Http::fake(['*' => Http::response(['value' => []])]);
        $this->fakeAuthentication();

        $records = app(Dynamics::class)->lazy('customers')->collect();

        $this->assertTrue($records->isEmpty());

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->data() === ['$top' => 1000, '$skip' => 0]);
    }

    #[Test]
    public function it_overwrites_a_top_and_skip_that_were_passed_along(): void
    {
        $this->setConnection();
        Http::fake(['*' => Http::response(['value' => []])]);
        $this->fakeAuthentication();

        app(Dynamics::class)->lazy('customers', ['$top' => 999, '$skip' => 999], 50)->collect();

        Http::assertSent(fn (Request $request): bool => $request->data() === ['$top' => 50, '$skip' => 0]
            && $request->url() === self::BASE_URL.'/customers?%24top=50&%24skip=0');
    }

    #[Test]
    public function it_throws_an_exception_when_a_page_fails(): void
    {
        $this->setConnection();
        Http::fake(['*' => Http::sequence()
            ->push(['value' => [['id' => '::first::'], ['id' => '::second::']]])
            ->push(status: 500),
        ]);
        $this->fakeAuthentication();

        $this->assertThrows(
            fn (): Collection => app(Dynamics::class)->lazy('customers', [], 2)->collect(),
            RequestException::class
        );

        Http::assertSentCount(2);
    }

    #[Test]
    public function it_can_update_a_scoped_entity(): void
    {
        $this->setConnection();
        Event::fake([DynamicsResponseEvent::class]);
        Http::fake();
        $this->fakeAuthentication();

        app(Dynamics::class)
            ->entity($this->customer())
            ->update(['displayName' => '::other-name::']);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'PATCH'
            && $request->url() === self::BASE_URL.'/customers(::id::)'
            && $request->data() === ['displayName' => '::other-name::']
            && $request->hasHeader('If-Match', 'W/"::etag::"'));

        Event::assertDispatched(DynamicsResponseEvent::class);
    }

    #[Test]
    public function it_can_update_an_entity_built_by_hand_without_an_etag(): void
    {
        $this->setConnection();
        Http::fake();
        $this->fakeAuthentication();

        $entity = Entity::make(['id' => '::id::'])->withMetadata(['endpoint' => 'customers']);

        app(Dynamics::class)
            ->entity($entity)
            ->update(['displayName' => '::name::']);

        Http::assertSent(fn (Request $request): bool => $request->url() === self::BASE_URL.'/customers(::id::)'
            && $request->hasHeader('If-Match', '*'));
    }

    #[Test]
    public function it_can_scope_an_entity_to_another_endpoint(): void
    {
        $this->setConnection();
        Http::fake();
        $this->fakeAuthentication();

        $entity = Entity::from(['id' => '::id::']);

        app(Dynamics::class)
            ->entity($entity, 'vendors')
            ->update(['displayName' => '::name::']);

        Http::assertSent(fn (Request $request): bool => $request->url() === self::BASE_URL.'/vendors(::id::)');
    }

    #[Test]
    public function it_can_update_only_the_changed_attributes_of_a_scoped_entity(): void
    {
        $this->setConnection();
        Http::fake();
        $this->fakeAuthentication();

        $entity = $this->customer();
        $entity->displayName = '::other-name::';

        app(Dynamics::class)->entity($entity)->update();

        Http::assertSent(fn (Request $request): bool => $request->data() === ['displayName' => '::other-name::']);
    }

    #[Test]
    public function it_can_throw_an_exception_when_updating_an_unchanged_entity(): void
    {
        $this->setConnection();
        Http::fake();
        Http::preventStrayRequests();

        $this->expectException(DynamicsException::class);
        $this->expectExceptionMessage('The scoped entity has no changed attributes to update.');

        app(Dynamics::class)->entity($this->customer())->update();
    }

    #[Test]
    public function it_can_throw_an_exception_when_updating_without_a_scoped_entity(): void
    {
        $this->setConnection();
        Http::fake();
        Http::preventStrayRequests();

        $this->expectException(DynamicsException::class);
        $this->expectExceptionMessage('No entity is scoped, call entity() first or pass a path.');

        app(Dynamics::class)->update(['displayName' => '::name::']);
    }

    #[Test]
    public function it_can_delete_a_scoped_entity_without_a_path(): void
    {
        $this->setConnection();
        Http::fake();
        $this->fakeAuthentication();

        app(Dynamics::class)->entity($this->customer())->delete();

        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
            && $request->url() === self::BASE_URL.'/customers(::id::)'
            && $request->hasHeader('If-Match', 'W/"::etag::"'));
    }

    #[Test]
    public function it_can_let_an_explicit_header_win_over_the_scoped_etag(): void
    {
        $this->setConnection();
        Http::fake();
        $this->fakeAuthentication();

        app(Dynamics::class)
            ->header('If-Match', '*')
            ->entity($this->customer())
            ->update(['displayName' => '::other-name::']);

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('If-Match', '*'));
    }

    #[Test]
    public function it_can_default_the_if_match_header_without_a_scoped_entity(): void
    {
        $this->setConnection();
        Http::fake();
        $this->fakeAuthentication();

        $dynamics = app(Dynamics::class);

        $dynamics->patch('customers(::id::)', ['displayName' => '::name::']);
        $dynamics->put('customers(::id::)', ['displayName' => '::name::']);
        $dynamics->delete('customers(::id::)');

        Http::assertSentCount(3);
        Http::assertSent(fn (Request $request): bool => $request->hasHeader('If-Match', '*'));
    }

    #[Test]
    public function it_can_clear_the_entity_scope_once_the_request_completed(): void
    {
        $this->setConnection();
        Http::fake();
        $this->fakeAuthentication();

        $dynamics = app(Dynamics::class);

        $dynamics->entity($this->customer())->update(['displayName' => '::other-name::']);

        $this->assertNotInstanceOf(Entity::class, $dynamics->getEntity());
        $this->assertSame([], $dynamics->getHeaders());
    }

    #[Test]
    public function it_can_check_availability(): void
    {
        $this->mock(ChecksAvailability::class, function (MockInterface $mock): void {
            $mock
                ->shouldReceive('check')
                ->with('default')
                ->once()
                ->andReturnFalse();
        });

        $this->assertFalse(app(Dynamics::class)->available());
    }

    #[Test]
    public function it_can_throw_an_exception_for_an_unavailable_connection(): void
    {
        $this->setConnection(['availability' => ['throw' => true]]);
        cache()->put(CheckAvailability::AVAILABLE_KEY.'default', false);

        Http::fake();
        Http::preventStrayRequests();

        $dynamics = app(Dynamics::class);

        $verbs = [
            fn (): Response => $dynamics->get('customers'),
            fn (): Response => $dynamics->post('customers', ['displayName' => '::name::']),
            fn (): Response => $dynamics->patch('customers(::id::)', ['displayName' => '::name::']),
            fn (): Response => $dynamics->put('customers(::id::)/picture/content'),
            fn (): Response => $dynamics->delete('customers(::id::)'),
            fn (): Response => $dynamics->entity($this->customer())->update(['displayName' => '::name::']),
        ];

        foreach ($verbs as $verb) {
            $this->assertThrows($verb, UnavailableException::class, 'Connection "default" is currently unavailable.');
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function it_can_throw_an_exception_for_an_unavailable_page(): void
    {
        $this->setConnection(['availability' => ['throw' => true]]);
        $this->fakeAuthentication();

        Http::fake([
            '*' => Http::response([
                'value' => [
                    ['id' => '::id-1::'],
                    ['id' => '::id-2::'],
                ],
            ]),
        ]);

        $this->mock(ChecksAvailability::class, function (MockInterface $mock): void {
            $mock
                ->shouldReceive('check')
                ->with('default')
                ->andReturn(true, false);
        });

        $this->assertThrows(
            fn (): Collection => app(Dynamics::class)->lazyEntities('customers', [], 2)->collect(),
            UnavailableException::class
        );

        Http::assertSentCount(1);
    }

    #[Test]
    public function it_can_skip_the_availability_guard_when_disabled(): void
    {
        $this->setConnection();
        $this->fakeAuthentication();
        cache()->put(CheckAvailability::AVAILABLE_KEY.'default', false);

        Http::fake();

        $response = app(Dynamics::class)->get('customers');

        $this->assertSame(200, $response->status());
        Http::assertSentCount(1);
    }

    #[Test]
    public function it_can_be_faked(): void
    {
        config()->set('dynamics.connection', '::configured::');
        config()->set('dynamics.connections', []);

        Dynamics::fake();

        $this->assertSame('default', config('dynamics.connection'));
        $this->assertSame('::client-id::', config('dynamics.connections.default.client_id'));
        $this->assertSame('::client-secret::', config('dynamics.connections.default.client_secret'));
        $this->assertSame('default', app(Dynamics::class)->getConnection());
    }

    #[Test]
    public function it_resolves_assertable_urls_when_faked(): void
    {
        Dynamics::fake();

        $dynamics = app(Dynamics::class);

        $this->assertSame(self::BASE_URL, $dynamics->baseUrl());
        $this->assertSame('https://login.microsoftonline.com/::tenant-id::/oauth2/v2.0/token', $dynamics->tokenUrl());
    }

    #[Test]
    public function it_fakes_the_oauth_token_request(): void
    {
        Dynamics::fake();

        Http::fake([
            'https://api.businesscentral.dynamics.com/*' => Http::response(['value' => []]),
        ]);

        $response = app(Dynamics::class)->get('customers');

        $this->assertSame(200, $response->status());
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://login.microsoftonline.com/::tenant-id::/oauth2/v2.0/token'
            && $request['client_id'] === '::client-id::');
        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer ::access-token::'));
    }
}
