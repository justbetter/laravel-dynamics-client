<?php

namespace JustBetter\DynamicsClient\Concerns;

trait HasCasts
{
    public array $casts = [];

    public function cast(string $key, mixed $value): string
    {
        return match ($this->casts[$key] ?? null) {
            'int', 'date', 'decimal' => (string) $value,
            default => '\''.$value.'\'',
        };
    }
}
