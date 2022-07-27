<?php

namespace JustBetter\DynamicsClient\Concerns;

trait HasKeys
{
    public array $primaryKey = [
        'No',
    ];

    public bool $includeKeyNames = true;

    /* Returns a key-value array of the primary key */
    public function getIdentifierData(): array
    {
        return collect($this->primaryKey)
            ->mapWithKeys(fn (string $key): array => [$key => $this[$key]])
            ->toArray();
    }

    public function getIdentifierString(): string
    {
        $values = collect($this->getIdentifierData());

        $includeKeyNames = $this->includeKeyNames && $values->count() > 1;

        return $values
            ->filter(fn (mixed $value): bool => $value !== null)
            ->map(function (mixed $value, string $key) use ($includeKeyNames): string {
                $cast = $this->cast($key, $value);

                return $includeKeyNames ? $key.'='.$cast : $cast;
            })
            ->implode(',');
    }

    /* Full OData URL for this specific resource */
    public function getResourceUrl(): string
    {
        return $this->endpoint.'('.$this->getIdentifierString().')';
    }
}
