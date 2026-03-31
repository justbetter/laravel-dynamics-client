<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\OData\Pages;

use JustBetter\DynamicsClient\OData\BaseResource;

class SalesShipmentLine extends BaseResource
{
    public array $primaryKey = [
        'Document_No',
        'Line_No',
    ];

    public array $casts = [
        'Line_No' => 'int',
    ];
}
