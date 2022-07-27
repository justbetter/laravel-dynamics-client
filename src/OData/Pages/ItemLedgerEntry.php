<?php

namespace JustBetter\DynamicsClient\OData\Pages;

use JustBetter\DynamicsClient\OData\BaseResource;

class ItemLedgerEntry extends BaseResource
{
    public array $primaryKey = [
        'Entry_No',
    ];

    public array $casts = [
        'Entry_No' => 'int',
    ];
}
