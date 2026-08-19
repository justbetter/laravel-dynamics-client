<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Data;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use JustBetter\DynamicsClient\Exceptions\DynamicsException;

/**
 * @property ?string $id
 *
 * @extends Data<string, mixed>
 */
class Entity extends Data
{
    public string $idKey = 'id';

    /** @var array<string, mixed> */
    protected array $metadata = [];

    /** @var array<string, mixed> */
    protected array $original = [];

    /** @param iterable<string, mixed> $attributes */
    public function __construct($attributes = [])
    {
        parent::__construct($attributes);

        $this->syncOriginal();
    }

    /**
     * @param  Response|array<string, mixed>  $data
     * @param  array<string, mixed>  $metadata
     */
    public static function from(Response|array $data, array $metadata = []): static
    {
        return static::make(static::body($data))->withMetadata($metadata);
    }

    /**
     * @param  Response|array<string, mixed>  $data
     * @param  array<string, mixed>  $metadata
     * @return Collection<int, static>
     */
    public static function collection(Response|array $data, array $metadata = []): Collection
    {
        /** @var array<int, array<string, mixed>> $value */
        $value = static::body($data)['value'] ?? [];

        return collect($value)
            ->map(fn (array $attributes): static => static::from($attributes, $metadata))
            ->values();
    }

    /**
     * @param  Response|array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function body(Response|array $data): array
    {
        if (is_array($data)) {
            return $data;
        }

        /** @var array<string, mixed> $body */
        $body = $data->json() ?? [];

        return $body;
    }

    public function etag(): ?string
    {
        $value = $this->value('@odata.etag');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function id(): ?string
    {
        $value = $this->value($this->idKey);

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function metadata(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->metadata;
        }

        return $this->metadata[$key] ?? null;
    }

    /** @param array<string, mixed> $metadata */
    public function withMetadata(array $metadata): static
    {
        $this->metadata = collect($this->metadata)
            ->merge($metadata)
            ->all();

        return $this;
    }

    public function endpoint(): ?string
    {
        $endpoint = $this->metadata('endpoint');

        return is_string($endpoint) && $endpoint !== '' ? $endpoint : null;
    }

    public function url(?string $endpoint = null): string
    {
        $endpoint ??= $this->endpoint();

        throw_if($endpoint === null, DynamicsException::class, 'Entity has no endpoint to build a URL with.');

        $id = $this->id();

        throw_if($id === null, DynamicsException::class, 'Entity has no "'.$this->idKey.'" attribute to build a URL with.');

        return $endpoint.'('.$id.')';
    }

    /** @return array<string, mixed> */
    public function dirty(): array
    {
        return collect($this->attributes)
            ->filter(fn (mixed $value, string $key): bool => ! array_key_exists($key, $this->original) || $this->original[$key] !== $value)
            ->all();
    }

    public function isDirty(): bool
    {
        return $this->dirty() !== [];
    }

    public function original(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->original;
        }

        return $this->original[$key] ?? null;
    }

    public function syncOriginal(): static
    {
        $this->original = $this->attributes;

        return $this;
    }

    /** @param  Response|array<string, mixed>  $data */
    public function sync(Response|array $data): static
    {
        $this->attributes = static::body($data);

        return $this->syncOriginal();
    }
}
