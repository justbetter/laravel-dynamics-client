<?php

namespace JustBetter\DynamicsClient\Data;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use JustBetter\DynamicsClient\Concerns\ValidatesData;

abstract class Data implements Arrayable, ArrayAccess
{
    use ValidatesData;

    final public function __construct(
        public array $data
    ) {
        $this->validate($data);
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }

    public static function of(array $data): static
    {
        return new static($data);
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
