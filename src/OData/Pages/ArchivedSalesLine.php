<?php

namespace JustBetter\DynamicsClient\OData\Pages;

use JustBetter\DynamicsClient\OData\BaseResource;

class ArchivedSalesLine extends BaseResource
{
    public array $primaryKey = [
        'Document_No',
        'Document_Type',
        'Doc_No_Occurrence',
        'Line_No',
        'Version_No',
    ];

    public array $casts = [
        'Doc_No_Occurrence' => 'int',
        'Version_No' => 'int',
    ];
}
