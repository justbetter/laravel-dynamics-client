<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Tests\Data;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use JustBetter\DynamicsClient\Data\Entity;
use JustBetter\DynamicsClient\Exceptions\DynamicsException;
use JustBetter\DynamicsClient\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class EntityTest extends TestCase
{
    #[Test]
    public function it_can_hydrate_an_entity_from_a_response(): void
    {
        $entity = Entity::from($this->response([
            '@odata.etag' => 'W/"::etag::"',
            'id' => '::id::',
            'displayName' => '::name::',
        ]), ['endpoint' => 'customers']);

        $this->assertSame('W/"::etag::"', $entity->etag());
        $this->assertSame('::id::', $entity->id());
        $this->assertSame('::name::', $entity->displayName);
        $this->assertSame('customers', $entity->endpoint());
        $this->assertFalse($entity->isDirty());
        $this->assertSame('::name::', $entity->original('displayName'));
    }

    #[Test]
    public function it_can_hydrate_an_entity_from_an_array(): void
    {
        $entity = Entity::from([
            'id' => '::id::',
            'displayName' => '::name::',
        ]);

        $this->assertSame('::id::', $entity->id());
        $this->assertSame(['id' => '::id::', 'displayName' => '::name::'], $entity->toArray());
        $this->assertSame([], $entity->dirty());
    }

    #[Test]
    public function it_can_hydrate_a_collection_from_a_list_response(): void
    {
        $entities = Entity::collection($this->response([
            '@odata.context' => '::context::',
            'value' => [
                ['id' => '::first::'],
                ['id' => '::second::'],
            ],
        ]), ['endpoint' => 'customers']);

        $this->assertCount(2, $entities);
        $this->assertSame(['::first::', '::second::'], $entities->map(fn (Entity $entity): ?string => $entity->id())->all());
        $this->assertSame('customers', $entities->first()?->endpoint());
    }

    #[Test]
    public function it_can_hydrate_an_empty_collection(): void
    {
        $this->assertTrue(Entity::collection([])->isEmpty());
    }

    #[Test]
    public function it_returns_null_for_a_missing_etag_and_id(): void
    {
        $entity = Entity::from(['@odata.etag' => '']);

        $this->assertNull($entity->etag());
        $this->assertNull($entity->id());
    }

    #[Test]
    public function it_can_get_its_metadata(): void
    {
        $entity = Entity::from(['id' => '::id::'], ['endpoint' => 'customers']);

        $entity->withMetadata(['connection' => '::connection::']);

        $this->assertSame(['endpoint' => 'customers', 'connection' => '::connection::'], $entity->metadata());
        $this->assertSame('customers', $entity->metadata('endpoint'));
        $this->assertNull($entity->metadata('unknown'));
        $this->assertSame('customers', $entity->endpoint());
    }

    #[Test]
    public function it_has_no_endpoint_without_metadata(): void
    {
        $this->assertNull(Entity::from(['id' => '::id::'])->endpoint());
    }

    #[Test]
    public function it_keeps_metadata_out_of_its_attributes(): void
    {
        $entity = Entity::from(['displayName' => '::name::'], ['endpoint' => 'customers']);

        $this->assertSame(['displayName' => '::name::'], $entity->toArray());
    }

    #[Test]
    public function it_tracks_dirty_attributes_through_every_write_style(): void
    {
        $entity = Entity::from([
            'id' => '::id::',
            'displayName' => '::name::',
            'blocked' => false,
            'city' => '::city::',
            'number' => '::number::',
        ]);

        $entity->fill(['displayName' => '::other-name::']);
        $entity->set('blocked', true);
        $entity->city = '::other-city::';
        $entity['number'] = '::other-number::';

        $this->assertTrue($entity->isDirty());
        $this->assertSame([
            'displayName' => '::other-name::',
            'blocked' => true,
            'city' => '::other-city::',
            'number' => '::other-number::',
        ], $entity->dirty());
        $this->assertSame('::name::', $entity->original('displayName'));
    }

    #[Test]
    public function it_compares_dirty_attributes_strictly(): void
    {
        $entity = Entity::from($this->response('{"id":"::id::","unitPrice":5.0}'));

        $this->assertIsFloat($entity->original('unitPrice'));
        $this->assertFalse($entity->isDirty());

        $entity->unitPrice = 5;

        $this->assertSame(['unitPrice' => 5], $entity->dirty());
    }

    #[Test]
    public function it_can_build_a_url(): void
    {
        $entity = Entity::from(['id' => '::id::'], ['endpoint' => 'customers']);

        $this->assertSame('customers(::id::)', $entity->url());
        $this->assertSame('vendors(::id::)', $entity->url('vendors'));
    }

    #[Test]
    public function it_cannot_build_a_url_without_an_endpoint(): void
    {
        $entity = Entity::from(['id' => '::id::']);

        $this->expectException(DynamicsException::class);
        $this->expectExceptionMessage('Entity has no endpoint to build a URL with.');

        $entity->url();
    }

    #[Test]
    public function it_cannot_build_a_url_without_an_id(): void
    {
        $entity = Entity::from(['displayName' => '::name::'], ['endpoint' => 'customers']);

        $this->expectException(DynamicsException::class);
        $this->expectExceptionMessage('Entity has no "id" attribute to build a URL with.');

        $entity->url();
    }

    #[Test]
    public function it_can_sync_a_response_body(): void
    {
        $entity = Entity::from([
            '@odata.etag' => 'W/"::etag::"',
            'id' => '::id::',
            'displayName' => '::name::',
        ], ['endpoint' => 'customers']);

        $entity->displayName = '::other-name::';

        $entity->sync($this->response([
            '@odata.etag' => 'W/"::new-etag::"',
            'id' => '::id::',
            'displayName' => '::other-name::',
        ]));

        $this->assertSame('W/"::new-etag::"', $entity->etag());
        $this->assertFalse($entity->isDirty());
        $this->assertSame('::other-name::', $entity->original('displayName'));
        $this->assertSame('customers', $entity->endpoint());
    }

    #[Test]
    public function it_can_sync_its_original_snapshot(): void
    {
        $entity = Entity::from(['displayName' => '::name::']);

        $entity->displayName = '::other-name::';

        $this->assertSame(['displayName' => '::name::'], $entity->original());

        $entity->syncOriginal();

        $this->assertSame(['displayName' => '::other-name::'], $entity->original());
        $this->assertFalse($entity->isDirty());
        $this->assertNull($entity->original('unknown'));
    }

    #[Test]
    public function it_performs_no_http_calls(): void
    {
        Http::fake();
        Http::preventStrayRequests();

        $entity = Entity::from(['id' => '::id::'], ['endpoint' => 'customers']);
        $entity->displayName = '::name::';

        $this->assertSame('customers(::id::)', $entity->url());

        Http::assertNothingSent();
    }

    /** @param array<string, mixed>|string $body */
    protected function response(array|string $body): Response
    {
        return new Response(new GuzzleResponse(
            200,
            ['Content-Type' => 'application/json'],
            is_string($body) ? $body : (string) json_encode($body)
        ));
    }
}
