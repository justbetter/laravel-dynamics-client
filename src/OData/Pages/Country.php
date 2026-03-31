<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\OData\Pages;

use JustBetter\DynamicsClient\OData\BaseResource;

class Country extends BaseResource
{
    public array $primaryKey = [
        'Code',
    ];
}
