<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Tests\Query;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Stringable;
use Iterator;
use JustBetter\DynamicsClient\Exceptions\DynamicsException;
use JustBetter\DynamicsClient\Exceptions\GrammarException;
use JustBetter\DynamicsClient\Query\Grammar;
use JustBetter\DynamicsClient\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class GrammarTest extends TestCase
{
    #[Test]
    #[DataProvider('operators')]
    public function it_can_map_operators(string $operator, string $expected): void
    {
        $grammar = app(Grammar::class);

        $this->assertSame($expected, $grammar->getOperator($operator));
    }

    public static function operators(): Iterator
    {
        yield 'equals' => ['operator' => '=', 'expected' => 'eq'];
        yield 'not equals' => ['operator' => '!=', 'expected' => 'ne'];
        yield 'not equals (sql)' => ['operator' => '<>', 'expected' => 'ne'];
        yield 'greater than' => ['operator' => '>', 'expected' => 'gt'];
        yield 'greater or equal' => ['operator' => '>=', 'expected' => 'ge'];
        yield 'less than' => ['operator' => '<', 'expected' => 'lt'];
        yield 'less or equal' => ['operator' => '<=', 'expected' => 'le'];
        yield 'odata eq' => ['operator' => 'eq', 'expected' => 'eq'];
        yield 'odata ne' => ['operator' => 'ne', 'expected' => 'ne'];
        yield 'odata gt' => ['operator' => 'gt', 'expected' => 'gt'];
        yield 'odata ge' => ['operator' => 'ge', 'expected' => 'ge'];
        yield 'odata lt' => ['operator' => 'lt', 'expected' => 'lt'];
        yield 'odata le' => ['operator' => 'le', 'expected' => 'le'];
        yield 'odata uppercase' => ['operator' => 'EQ', 'expected' => 'eq'];
    }

    #[Test]
    public function it_throws_an_exception_for_an_unknown_operator(): void
    {
        $grammar = app(Grammar::class);

        $this->expectException(GrammarException::class);
        $this->expectExceptionMessage('Operator "like" is unknown.');

        $grammar->getOperator('like');
    }

    #[Test]
    #[DataProvider('values')]
    public function it_can_format_values(mixed $value, string $expected): void
    {
        $grammar = app(Grammar::class);

        $this->assertSame($expected, $grammar->value($value));
    }

    public static function values(): Iterator
    {
        yield 'string' => ['value' => '::city::', 'expected' => "'::city::'"];
        yield 'string with a single quote' => ['value' => "::o'reilly::", 'expected' => "'::o''reilly::'"];
        yield 'empty string' => ['value' => '', 'expected' => "''"];
        yield 'integer' => ['value' => 1000, 'expected' => '1000'];
        yield 'float' => ['value' => 12.5, 'expected' => '12.5'];
        yield 'true' => ['value' => true, 'expected' => 'true'];
        yield 'false' => ['value' => false, 'expected' => 'false'];
        yield 'null' => ['value' => null, 'expected' => 'null'];
        yield 'stringable' => ['value' => new Stringable("::o'reilly::"), 'expected' => "'::o''reilly::'"];
        yield 'immutable date' => [
            'value' => new DateTimeImmutable('2026-08-18 00:00:00', new DateTimeZone('UTC')),
            'expected' => '2026-08-18T00:00:00Z',
        ];
        yield 'date in another timezone' => [
            'value' => new DateTime('2026-08-18 02:00:00', new DateTimeZone('Europe/Amsterdam')),
            'expected' => '2026-08-18T00:00:00Z',
        ];
    }

    #[Test]
    public function it_does_not_mutate_the_given_date(): void
    {
        $grammar = app(Grammar::class);

        $date = new DateTime('2026-08-18 02:00:00', new DateTimeZone('Europe/Amsterdam'));

        $grammar->value($date);

        $this->assertSame('Europe/Amsterdam', $date->getTimezone()->getName());
    }

    #[Test]
    public function it_throws_an_exception_for_an_unsupported_value(): void
    {
        $grammar = app(Grammar::class);

        $this->expectException(GrammarException::class);
        $this->expectExceptionMessage('Value of type "array" cannot be formatted.');

        $grammar->value(['::city::']);
    }

    #[Test]
    public function it_can_format_a_guid_unquoted(): void
    {
        $grammar = app(Grammar::class);

        $this->assertSame(
            '4c5bd07f-5f0f-ee11-8f6e-6045bd8f0b9c',
            $grammar->guid('4c5bd07f-5f0f-ee11-8f6e-6045bd8f0b9c')
        );
    }

    #[Test]
    public function it_throws_an_exception_for_an_invalid_guid(): void
    {
        $grammar = app(Grammar::class);

        $this->expectException(GrammarException::class);
        $this->expectExceptionMessage('Value "::no-1::\' or No eq \'::no-2::" is not a valid GUID.');

        $grammar->guid("::no-1::' or No eq '::no-2::");
    }

    #[Test]
    public function it_can_catch_a_grammar_exception_as_a_dynamics_exception(): void
    {
        $grammar = app(Grammar::class);

        $this->assertThrows(fn (): string => $grammar->getOperator('like'), DynamicsException::class);
    }
}
