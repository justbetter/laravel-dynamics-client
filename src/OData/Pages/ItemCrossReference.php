<?php

namespace JustBetter\DynamicsClient\OData\Pages;

use JustBetter\DynamicsClient\OData\BaseResource;

class ItemCrossReference extends BaseResource
{
    public array $primaryKey = [
        'Cross_Reference_No',
        'Cross_Reference_Type',
        'Cross_Reference_Type_No',
        'Item_No',
        'Unit_of_Measure',
        'Variant_Code',
    ];
}
