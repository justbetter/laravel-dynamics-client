<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Query;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use JustBetter\DynamicsClient\Exceptions\GrammarException;
use Stringable;

class Grammar
{
    /** @var array<string, string> */
    protected const array MAPPING = [
        '=' => 'eq',
        '!=' => 'ne',
        '<>' => 'ne',
        '>' => 'gt',
        '>=' => 'ge',
        '<' => 'lt',
        '<=' => 'le',
    ];

    /** @var array<int, string> */
    protected const array OPERATORS = [
        'eq',
        'ne',
        'gt',
        'ge',
        'lt',
        'le',
    ];

    public const string DATE_FORMAT = 'Y-m-d\TH:i:s\Z';

    public const string GUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    public function getOperator(string $operator): string
    {
        $normalized = strtolower(trim($operator));

        if (in_array($normalized, static::OPERATORS, true)) {
            return $normalized;
        }

        return static::MAPPING[$normalized] ?? throw new GrammarException('Operator "'.$operator.'" is unknown.');
    }

    public function value(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_int($value), is_float($value) => (string) $value,
            $value instanceof DateTimeInterface => $this->date($value),
            is_string($value), $value instanceof Stringable => $this->quote((string) $value),
            default => throw new GrammarException('Value of type "'.get_debug_type($value).'" cannot be formatted.'),
        };
    }

    public function guid(string $value): string
    {
        throw_if(preg_match(static::GUID_PATTERN, $value) !== 1, GrammarException::class, 'Value "'.$value.'" is not a valid GUID.');

        return $value;
    }

    protected function quote(string $value): string
    {
        return "'".str_replace("'", "''", $value)."'";
    }

    protected function date(DateTimeInterface $value): string
    {
        return DateTimeImmutable::createFromInterface($value)
            ->setTimezone(new DateTimeZone('UTC'))
            ->format(static::DATE_FORMAT);
    }
}
