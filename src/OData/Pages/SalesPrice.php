<?php

namespace JustBetter\DynamicsClient\OData\Pages;

use JustBetter\DynamicsClient\OData\BaseResource;

class SalesPrice extends BaseResource
{
    public array $primaryKey = [
        'Item_No',
        'Sales_Type',
        'Sales_Code',
        'Starting_Date',
        'Currency_Code',
        'Variant_Code',
        'Unit_of_Measure_Code',
        'Minimum_Quantity',
    ];

    public array $casts = [
        'Starting_Date' => 'date',
        'Minimum_Quantity' => 'decimal',
    ];
}
