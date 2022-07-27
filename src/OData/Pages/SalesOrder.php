<?php

namespace JustBetter\DynamicsClient\OData\Pages;

use JustBetter\DynamicsClient\OData\BaseResource;

class SalesOrder extends BaseResource
{
    public array $primaryKey = [
        'Document_Type',
        'No',
    ];
}
