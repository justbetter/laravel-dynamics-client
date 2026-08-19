<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Tests\Data;

use Illuminate\Validation\ValidationException;
use JustBetter\DynamicsClient\Tests\Fakes\FakeData;
use JustBetter\DynamicsClient\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class DataTest extends TestCase
{
    #[Test]
    public function it_can_validate_its_attributes(): void
    {
        $data = FakeData::make(['value' => '::value::'])->validate();

        $this->assertSame('::value::', $data->value);
    }

    #[Test]
    public function it_can_throw_an_exception_for_invalid_attributes(): void
    {
        $this->expectException(ValidationException::class);

        FakeData::make()->validate();
    }
}
