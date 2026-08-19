<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Tests\Fakes;

use JustBetter\DynamicsClient\Data\Data;

/**
 * @property string $value
 *
 * @extends Data<string, mixed>
 */
class FakeData extends Data
{
    /** @var array<string, string> */
    protected array $rules = [
        'value' => 'required|string',
    ];
}
