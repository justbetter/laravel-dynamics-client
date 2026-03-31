<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Tests\Fakes\OData;

use JustBetter\DynamicsClient\OData\BaseResource;

class FakeResource extends BaseResource
{
    public string $connection = '::fake-connection::';

    public string $endpoint = '::fake-endpoint::';

    public array $primaryKey = [
        'No',
    ];
}
