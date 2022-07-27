<?php

namespace JustBetter\DynamicsClient\OData\Pages;

use JustBetter\DynamicsClient\OData\BaseResource;

class ArchivedSalesOrder extends BaseResource
{
    public array $primaryKey = [
        'Document_Type',
        'Doc_No_Occurrence',
        'No',
        'Version_No',
    ];

    public array $casts = [
        'Doc_No_Occurrence' => 'int',
        'Version_No' => 'int',
    ];
}
