<?php

namespace JustBetter\DynamicsClient\OData;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use JustBetter\DynamicsClient\Client\ClientFactory;
use JustBetter\DynamicsClient\Concerns\CanBeSerialized;
use JustBetter\DynamicsClient\Concerns\HasCasts;
use JustBetter\DynamicsClient\Concerns\HasData;
use JustBetter\DynamicsClient\Concerns\HasKeys;
use JustBetter\DynamicsClient\Query\QueryBuilder;
use SaintSystems\OData\Entity;
use SaintSystems\OData\ODataClient;

abstract class BaseResource implements ArrayAccess, Arrayable
{
    use HasData;
    use HasKeys;
    use HasCasts;
    use CanBeSerialized;

    public string $connection;

    public string $endpoint;

    public function __construct(?string $connection = null, ?string $endpoint = null)
    {
        $this->connection = $connection ?? config('dynamics.connection');
        $this->endpoint = $endpoint ?? config('dynamics.resources.'.static::class, Str::afterLast(static::class, '\\'));
    }

    public static function new(?string $connection = null, ?string $endpoint = null): static
    {
        return new static($connection, $endpoint);
    }

    public static function query(?string $connection = null, ?string $endpoint = null): QueryBuilder
    {
        return static::new($connection, $endpoint)->newQuery();
    }

    public function setConnection(string $connection): static
    {
        $this->connection = $connection;

        return $this;
    }

    public function setEndpoint(string $endpoint): static
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    public function fromEntity(Entity $entity): static
    {
        $this->setData($entity->toArray());

        return $this;
    }

    public function fromPage(BaseResource $page): static
    {
        $this->setData($page->getData());

        return $this;
    }

    public function create(array $data): ?static
    {
        /** @var array<int, Entity> $entities */
        $entities = $this->client()->post($this->endpoint, $data);

        if (empty($entities)) {
            return null;
        }

        $entity = reset($entities);

        return static::new($this->connection, $this->endpoint)->fromEntity($entity);
    }

    public function update(array $data, bool $force = false): ?static
    {
        $this
            ->client($this->etag($force))
            ->patch($this->getResourceUrl(), $data);

        return $this->refresh();
    }

    public function delete(bool $force = false): void
    {
        $this
            ->client($this->etag($force))
            ->delete($this->getResourceUrl());
    }

    public function refresh(): static
    {
        $values = collect($this->primaryKey)
            ->map(fn (string $key): mixed => $this[$key])
            ->toArray();

        $baseResource = static::query($this->connection, $this->endpoint)->find(...$values);

        return $this->fromPage($baseResource);
    }

    public function etag(bool $force = false): string
    {
        return $force ? '*' : $this->data['ETag'];
    }

    public function client(?string $etag = null): ODataClient
    {
        $factory = ClientFactory::make($this->connection);

        if ($etag) {
            $factory->etag($etag);
        }

        return $factory->fabricate();
    }

    public function newQuery(): QueryBuilder
    {
        return new QueryBuilder($this->client(), $this->connection, $this->endpoint, static::class);
    }
}
