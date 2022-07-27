<?php

namespace JustBetter\DynamicsClient\OData\Pages;

use JustBetter\DynamicsClient\OData\BaseResource;

class ShipToAddress extends BaseResource
{
    public array $primaryKey = [
        'Customer_No',
        'Code',
    ];
}
