<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\OData\Pages;

use JustBetter\DynamicsClient\OData\BaseResource;

class SalesHeader extends BaseResource
{
    public array $primaryKey = [
        'Document_Type',
        'No',
    ];
}
