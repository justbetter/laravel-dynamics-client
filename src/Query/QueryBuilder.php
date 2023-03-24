<?php

namespace JustBetter\DynamicsClient\Query;

use Closure;
use Generator;
use Illuminate\Support\Enumerable;
use Illuminate\Support\LazyCollection;
use JustBetter\DynamicsClient\Exceptions\NotFoundException;
use JustBetter\DynamicsClient\OData\BaseResource;
use SaintSystems\OData\Entity;
use SaintSystems\OData\ODataClient;
use SaintSystems\OData\Query\Builder;

/** @mixin Builder */
class QueryBuilder
{
    protected Builder $builder;

    public function __construct(
        public ODataClient $client,
        public string $connection,
        public string $endpoint,
        public string $class,
    ) {
        $this->builder = (new Builder($client))->from($endpoint);
    }

    public function __call(string $name, array $arguments): mixed
    {
        if (method_exists($this, $name)) {
            return $this->$name(...$arguments);
        }

        $this->builder->$name(...$arguments);

        return $this;
    }

    public function newResourceInstance(): BaseResource
    {
        /** @var class-string<BaseResource> $class */
        $class = $this->class;

        return $class::new($this->connection, $this->endpoint);
    }

    public function mapToClass(Entity $entity): BaseResource
    {
        return $this->newResourceInstance()->fromEntity($entity);
    }

    public function get(): Enumerable
    {
        return $this->builder->get()->map(fn (Entity $entity): BaseResource => $this->mapToClass($entity));
    }

    public function first(): ?BaseResource
    {
        /** @var ?Entity $entity */
        $entity = $this->builder->first();

        return is_null($entity)
            ? null
            : $this->mapToClass($entity);
    }

    public function firstOrFail(): BaseResource
    {
        $resource = $this->first();

        if ($resource === null) {
            throw new NotFoundException();
        }

        return $resource;
    }

    public function find(mixed ...$values): ?BaseResource
    {
        $baseResource = $this->newResourceInstance();

        $combined = array_combine($baseResource->primaryKey, $values);

        foreach ($combined as $key => $value) {
            if ($baseResource->getCastType($key) === 'date') {
                $this->builder->whereDate($key, '=', $value);
            } else {
                $this->builder->where($key, '=', $value);
            }
        }

        /** @var ?Entity $entity */
        $entity = $this->builder->first();

        return is_null($entity)
            ? null
            : $this->mapToClass($entity);
    }

    public function findOrFail(mixed ...$values): BaseResource
    {
        $resource = $this->find(...$values);

        if ($resource === null) {
            throw new NotFoundException();
        }

        return $resource;
    }

    public function firstOrCreate(array $attributes = [], array $values = []): BaseResource
    {
        /** @var ?BaseResource $resource */
        $resource = $this->where($attributes)->first();

        if ($resource !== null) {
            return $resource;
        }

        $data = array_merge($attributes, $values);

        return $this->newResourceInstance()->create($data);
    }

    public function lazy(?int $pageSize = null): LazyCollection
    {
        return LazyCollection::make(function () use ($pageSize): Generator {
            $pageSize ??= (int) config('dynamics.connections.'.$this->connection.'.page_size');
            $page = 0;

            $hasNext = true;

            while ($hasNext) {
                if ($page > 0) {
                    $this->builder->skip($page * $pageSize);
                }

                $this->builder->take($pageSize);

                $records = $this->get();

                $hasNext = $records->count() === $pageSize;

                foreach ($records as $record) {
                    yield $record;
                }

                $page++;
            }
        });
    }

    public function limit(int $limit): static
    {
        $this->builder->take($limit);

        return $this;
    }

    public function whereIn(string $field, array $values): static
    {
        $this->builder->where(function (Builder $builder) use ($field, $values): void {
            foreach (array_values($values) as $index => $value) {
                $method = $index === 0 ? 'where' : 'orWhere';

                $builder->$method($field, '=', $value);
            }
        });

        return $this;
    }

    public function when(mixed $statement, Closure $closure): static
    {
        if ($statement) {
            $closure($this, $statement);
        }

        return $this;
    }

    public function dd(): void
    {
        dd($this->builder->toRequest());
    }
}
