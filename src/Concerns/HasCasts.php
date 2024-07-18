<?php

namespace JustBetter\DynamicsClient\Concerns;

trait HasCasts
{
    public array $casts = [];

    public function getCastType(string $key): ?string
    {
        return $this->casts[$key] ?? null;
    }

    public function cast(string $key, mixed $value): string
    {
        return match ($this->getCastType($key)) {
            'int', 'date', 'decimal', 'guid' => (string) $value,
            default => '\''.$value.'\'',
        };
    }
}
