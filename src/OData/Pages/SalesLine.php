<?php

namespace JustBetter\DynamicsClient\OData\Pages;

use JustBetter\DynamicsClient\OData\BaseResource;

class SalesLine extends BaseResource
{
    public array $primaryKey = [
        'Document_No',
        'Document_Type',
        'Line_No',
    ];

    public array $casts = [
        'Line_No' => 'int',
    ];
}
