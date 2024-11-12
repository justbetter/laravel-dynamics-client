<?php

namespace JustBetter\DynamicsClient\OData;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use JustBetter\DynamicsClient\Concerns\CanBeSerialized;
use JustBetter\DynamicsClient\Concerns\HasCasts;
use JustBetter\DynamicsClient\Concerns\HasData;
use JustBetter\DynamicsClient\Concerns\HasKeys;
use JustBetter\DynamicsClient\Contracts\Availability\ChecksAvailability;
use JustBetter\DynamicsClient\Contracts\ClientFactoryContract;
use JustBetter\DynamicsClient\Exceptions\DynamicsException;
use JustBetter\DynamicsClient\Query\QueryBuilder;
use SaintSystems\OData\Entity;
use SaintSystems\OData\ODataClient;

abstract class BaseResource implements Arrayable, ArrayAccess
{
    use CanBeSerialized;
    use HasCasts;
    use HasData;
    use HasKeys;

    public string $connection;

    public string $endpoint;

    public bool $wasRecentlyCreated = false;

    final public function __construct(?string $connection = null, ?string $endpoint = null)
    {
        $this->connection ??= $connection ?? config('dynamics.connection');
        $this->endpoint ??= $endpoint ?? config('dynamics.resources.'.static::class,
            Str::afterLast(static::class, '\\'));
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

    public function create(array $data): static
    {
        /** @var array<int, Entity> $entities */
        $entities = $this->client()->post($this->endpoint, $data);

        if (empty($entities)) {
            throw new DynamicsException('No data returned after creation');
        }

        /** @var Entity $entity */
        $entity = reset($entities);

        return static::new($this->connection, $this->endpoint)->fromEntity($entity)->wasRecentlyCreated();
    }

    protected function wasRecentlyCreated(bool $wasRecentlyCreated = true): static
    {
        $this->wasRecentlyCreated = $wasRecentlyCreated;

        return $this;
    }

    public function update(array $data, bool $force = false): static
    {
        $this
            ->client($this->etag($force))
            ->patch($this->getResourceUrl(), $data);

        $this->merge($data);

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

        $baseResource = static::query($this->connection, $this->endpoint)->findOrFail(...$values);

        return $this->fromPage($baseResource);
    }

    public function etag(bool $force = false): string
    {
        if ($force) {
            return '*';
        }

        $version = config('dynamics.connections.'.$this->connection.'.version');

        return $version === 'ODataV4'
            ? $this->data['@odata.etag']
            : $this->data['odata.etag'];
    }

    public function client(?string $etag = null): ODataClient
    {
        /** @var ClientFactoryContract $factory */
        $factory = app(ClientFactoryContract::class, ['connection' => $this->connection]);

        if ($etag) {
            $factory->etag($etag);
        }

        return $factory->fabricate();
    }

    public function newQuery(): QueryBuilder
    {
        return new QueryBuilder($this->client(), $this->connection, $this->endpoint, static::class);
    }

    public function relation(string $relation, string $class): QueryBuilder
    {
        return new QueryBuilder($this->client(), $this->connection, $this->getResourceUrl().'/'.$relation, $class);
    }

    public function available(): bool
    {
        /** @var ChecksAvailability $instance */
        $instance = app(ChecksAvailability::class);

        return $instance->check($this->connection);
    }

    public static function fake(): void
    {
        foreach (config('dynamics.connections') as $connection => $data) {
            config()->set('dynamics.connections.'.$connection.'.base_url', 'dynamics');
            config()->set('dynamics.connections.'.$connection.'.version', 'ODataV4');
            config()->set('dynamics.connections.'.$connection.'.company', $data['company'] ?? $connection);
            config()->set('dynamics.connections.'.$connection.'.username', 'username');
            config()->set('dynamics.connections.'.$connection.'.password', 'password');
            config()->set('dynamics.connections.'.$connection.'.auth', 'basic');
        }
    }
}
