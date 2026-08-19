<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Tests\Query;

use JustBetter\DynamicsClient\Exceptions\GrammarException;
use JustBetter\DynamicsClient\Query\QueryBuilder;
use JustBetter\DynamicsClient\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class QueryBuilderTest extends TestCase
{
    #[Test]
    public function it_resolves_through_the_container_with_a_grammar(): void
    {
        $builder = QueryBuilder::make();

        $this->assertInstanceOf(QueryBuilder::class, $builder);
        $this->assertSame([], $builder->get());
    }

    #[Test]
    public function it_only_returns_the_options_that_were_set(): void
    {
        $query = QueryBuilder::make()
            ->where('City', '::city::')
            ->select(['No', 'City'])
            ->expand('salesLines')
            ->orderBy('No')
            ->take(10)
            ->skip(20)
            ->get();

        $this->assertSame([
            '$filter' => "City eq '::city::'",
            '$select' => 'No,City',
            '$expand' => 'salesLines',
            '$orderby' => 'No asc',
            '$top' => 10,
            '$skip' => 20,
        ], $query);
    }

    #[Test]
    public function it_can_select_and_expand_a_single_field_as_a_string(): void
    {
        $query = QueryBuilder::make()
            ->select('No')
            ->expand(['salesLines', 'dimensions'])
            ->get();

        $this->assertSame([
            '$select' => 'No',
            '$expand' => 'salesLines,dimensions',
        ], $query);
    }

    #[Test]
    public function it_treats_a_two_argument_where_as_equality(): void
    {
        $query = QueryBuilder::make()
            ->where('City', '::city::')
            ->get();

        $this->assertSame(['$filter' => "City eq '::city::'"], $query);
    }

    #[Test]
    public function it_can_use_an_explicit_operator(): void
    {
        $query = QueryBuilder::make()
            ->where('Amount', '>=', 100)
            ->get();

        $this->assertSame(['$filter' => 'Amount ge 100'], $query);
    }

    #[Test]
    public function it_throws_an_exception_for_an_operator_that_is_not_a_string(): void
    {
        $this->expectException(GrammarException::class);
        $this->expectExceptionMessage('Operator of type "int" is unknown.');

        QueryBuilder::make()->where('Amount', 100, 100);
    }

    #[Test]
    public function it_joins_groups_with_and(): void
    {
        $query = QueryBuilder::make()
            ->where('City', '::city::')
            ->where('Country', '::country::')
            ->get();

        $this->assertSame(['$filter' => "City eq '::city::' and Country eq '::country::'"], $query);
    }

    #[Test]
    public function it_joins_conditions_within_a_group_with_or_and_wraps_them(): void
    {
        $query = QueryBuilder::make()
            ->where('City', '::city::')
            ->orWhere('City', '::other-city::')
            ->where('Country', '::country::')
            ->get();

        $this->assertSame([
            '$filter' => "(City eq '::city::' or City eq '::other-city::') and Country eq '::country::'",
        ], $query);
    }

    #[Test]
    public function it_starts_a_group_when_or_where_is_the_first_condition(): void
    {
        $query = QueryBuilder::make()
            ->orWhere('City', '::city::')
            ->get();

        $this->assertSame(['$filter' => "City eq '::city::'"], $query);
    }

    #[Test]
    public function it_can_add_a_where_in(): void
    {
        $query = QueryBuilder::make()
            ->whereIn('No', ['::no-1::', '::no-2::'])
            ->get();

        $this->assertSame(['$filter' => "(No eq '::no-1::' or No eq '::no-2::')"], $query);
    }

    #[Test]
    public function it_can_add_a_where_not_in(): void
    {
        $query = QueryBuilder::make()
            ->whereNotIn('No', ['::no-1::', '::no-2::'])
            ->get();

        $this->assertSame(['$filter' => "(No ne '::no-1::' and No ne '::no-2::')"], $query);
    }

    #[Test]
    public function it_keeps_a_where_not_in_grouped_next_to_other_conditions(): void
    {
        $query = QueryBuilder::make()
            ->where('City', '::city::')
            ->whereNotIn('No', ['::no-1::', '::no-2::'])
            ->get();

        $this->assertSame([
            '$filter' => "City eq '::city::' and (No ne '::no-1::' and No ne '::no-2::')",
        ], $query);
    }

    #[Test]
    public function it_can_add_null_conditions(): void
    {
        $query = QueryBuilder::make()
            ->whereNull('City')
            ->whereNotNull('Country')
            ->get();

        $this->assertSame(['$filter' => 'City eq null and Country ne null'], $query);
    }

    #[Test]
    public function it_can_add_null_conditions_to_the_previous_group(): void
    {
        $query = QueryBuilder::make()
            ->where('City', '::city::')
            ->orWhereNull('City')
            ->where('Country', '::country::')
            ->orWhereNotNull('Region')
            ->get();

        $this->assertSame([
            '$filter' => "(City eq '::city::' or City eq null) and (Country eq '::country::' or Region ne null)",
        ], $query);
    }

    #[Test]
    public function it_can_add_an_unquoted_guid(): void
    {
        $query = QueryBuilder::make()
            ->whereGuid('id', '4c5bd07f-5f0f-ee11-8f6e-6045bd8f0b9c')
            ->get();

        $this->assertSame(['$filter' => 'id eq 4c5bd07f-5f0f-ee11-8f6e-6045bd8f0b9c'], $query);
    }

    #[Test]
    public function it_refuses_a_guid_that_is_not_a_guid(): void
    {
        $this->expectException(GrammarException::class);

        QueryBuilder::make()->whereGuid('id', "::no-1::' or No eq '::no-2::");
    }

    #[Test]
    public function it_can_add_a_raw_filter(): void
    {
        $query = QueryBuilder::make()
            ->where('City', '::city::')
            ->whereRaw("startswith(No,'::no::')")
            ->get();

        $this->assertSame([
            '$filter' => "City eq '::city::' and startswith(No,'::no::')",
        ], $query);
    }

    #[Test]
    public function it_can_order_ascending_and_descending(): void
    {
        $query = QueryBuilder::make()
            ->orderBy('No')
            ->orderBy('City', 'DESC')
            ->orderByDesc('Amount')
            ->get();

        $this->assertSame(['$orderby' => 'No asc,City desc,Amount desc'], $query);
    }

    #[Test]
    public function it_falls_back_to_ascending_for_an_unknown_direction(): void
    {
        $query = QueryBuilder::make()
            ->orderBy('No', '::unknown::')
            ->get();

        $this->assertSame(['$orderby' => 'No asc'], $query);
    }

    #[Test]
    public function it_can_limit_the_amount_of_records(): void
    {
        $query = QueryBuilder::make()
            ->limit(50)
            ->get();

        $this->assertSame(['$top' => 50], $query);
    }

    #[Test]
    public function it_can_paginate(): void
    {
        $query = QueryBuilder::make()
            ->paginate(3, 20)
            ->get();

        $this->assertSame([
            '$top' => 20,
            '$skip' => 40,
        ], $query);
    }

    #[Test]
    public function it_only_applies_a_callback_when_the_condition_is_truthy(): void
    {
        $query = QueryBuilder::make()
            ->when('::city::', fn (QueryBuilder $builder): QueryBuilder => $builder->where('City', '::city::'))
            ->when(null, fn (QueryBuilder $builder): QueryBuilder => $builder->where('Country', '::country::'))
            ->get();

        $this->assertSame(['$filter' => "City eq '::city::'"], $query);
    }

    #[Test]
    public function it_builds_the_documented_chain(): void
    {
        $query = QueryBuilder::make()
            ->where('City', '::city::')
            ->whereIn('No', ['::no-1::', '::no-2::'])
            ->get();

        $this->assertSame([
            '$filter' => "City eq '::city::' and (No eq '::no-1::' or No eq '::no-2::')",
        ], $query);
    }
}
