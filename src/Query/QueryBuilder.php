<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Query;

use Closure;
use Illuminate\Support\Arr;
use JustBetter\DynamicsClient\Exceptions\GrammarException;

class QueryBuilder
{
    /** @var array<int, array<int, string>> */
    protected array $groups = [];

    protected ?string $select = null;

    protected ?string $expand = null;

    /** @var array<int, string> */
    protected array $orders = [];

    protected ?int $top = null;

    protected ?int $skip = null;

    public function __construct(
        protected Grammar $grammar
    ) {}

    public static function make(): static
    {
        return app(static::class);
    }

    /** @param array<int, string>|string $fields */
    public function select(array|string $fields): static
    {
        $this->select = collect(Arr::wrap($fields))->implode(',');

        return $this;
    }

    /** @param array<int, string>|string $relations */
    public function expand(array|string $relations): static
    {
        $this->expand = collect(Arr::wrap($relations))->implode(',');

        return $this;
    }

    public function where(string $field, mixed $operator = null, mixed $value = null): static
    {
        return $this->addGroup($this->resolve($field, $operator, $value));
    }

    public function orWhere(string $field, mixed $operator = null, mixed $value = null): static
    {
        return $this->addToGroup($this->resolve($field, $operator, $value));
    }

    /** @param array<int, mixed> $values */
    public function whereIn(string $field, array $values): static
    {
        $this->groups[] = collect($values)
            ->map(fn (mixed $value): string => $this->condition($field, '=', $value))
            ->all();

        return $this;
    }

    /** @param array<int, mixed> $values */
    public function whereNotIn(string $field, array $values): static
    {
        $conditions = collect($values)
            ->map(fn (mixed $value): string => $this->condition($field, '!=', $value))
            ->implode(' and ');

        return $this->whereRaw('('.$conditions.')');
    }

    public function whereNull(string $field): static
    {
        return $this->addGroup($this->condition($field, '=', null));
    }

    public function orWhereNull(string $field): static
    {
        return $this->addToGroup($this->condition($field, '=', null));
    }

    public function whereNotNull(string $field): static
    {
        return $this->addGroup($this->condition($field, '!=', null));
    }

    public function orWhereNotNull(string $field): static
    {
        return $this->addToGroup($this->condition($field, '!=', null));
    }

    public function whereGuid(string $field, string $value): static
    {
        return $this->addGroup(
            $field.' '.$this->grammar->getOperator('=').' '.$this->grammar->guid($value)
        );
    }

    public function whereRaw(string $filter): static
    {
        return $this->addGroup($filter);
    }

    public function orderBy(string $field, string $direction = 'asc'): static
    {
        $this->orders[] = $field.' '.$this->direction($direction);

        return $this;
    }

    public function orderByDesc(string $field): static
    {
        return $this->orderBy($field, 'desc');
    }

    public function skip(int $skip): static
    {
        $this->skip = $skip;

        return $this;
    }

    public function take(int $take): static
    {
        $this->top = $take;

        return $this;
    }

    public function limit(int $limit): static
    {
        return $this->take($limit);
    }

    public function paginate(int $page, int $pageSize): static
    {
        return $this->skip(($page - 1) * $pageSize)->take($pageSize);
    }

    public function when(mixed $condition, Closure $callback): static
    {
        if ($condition) {
            $callback($this);
        }

        return $this;
    }

    /** @return array<string, string|int> */
    public function get(): array
    {
        $filter = $this->compileFilter();

        return collect([
            '$filter' => $filter === '' ? null : $filter,
            '$select' => $this->select,
            '$expand' => $this->expand,
            '$orderby' => $this->orders === [] ? null : collect($this->orders)->implode(','),
            '$top' => $this->top,
            '$skip' => $this->skip,
        ])
            ->reject(fn (string|int|null $value): bool => $value === null)
            ->all();
    }

    protected function resolve(string $field, mixed $operator, mixed $value): string
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        throw_if(! is_string($operator), GrammarException::class, 'Operator of type "'.get_debug_type($operator).'" is unknown.');

        return $this->condition($field, $operator, $value);
    }

    protected function condition(string $field, string $operator, mixed $value): string
    {
        return $field.' '.$this->grammar->getOperator($operator).' '.$this->grammar->value($value);
    }

    protected function direction(string $direction): string
    {
        return strtolower(trim($direction)) === 'desc'
            ? 'desc'
            : 'asc';
    }

    protected function compileFilter(): string
    {
        return collect($this->groups)
            ->map(function (array $conditions): string {
                $filter = collect($conditions)->implode(' or ');

                return count($conditions) > 1 ? '('.$filter.')' : $filter;
            })
            ->implode(' and ');
    }

    protected function addGroup(string $condition): static
    {
        $this->groups[] = [$condition];

        return $this;
    }

    protected function addToGroup(string $condition): static
    {
        if ($this->groups === []) {
            return $this->addGroup($condition);
        }

        $this->groups[count($this->groups) - 1][] = $condition;

        return $this;
    }
}
