<?php

namespace JustBetter\DynamicsClient\OData\Pages;

use JustBetter\DynamicsClient\OData\BaseResource;
use JustBetter\DynamicsClient\Query\QueryBuilder;

class SalesOrder extends BaseResource
{
    public array $primaryKey = [
        'Document_Type',
        'No',
    ];

    public function lines(string $relation): QueryBuilder
    {
        return $this->relation($relation, SalesLine::class);
    }
}
