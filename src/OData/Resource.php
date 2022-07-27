<?php

namespace JustBetter\DynamicsClient\OData;

class Resource extends BaseResource
{
    public function __construct(string $endpoint, ?string $connection = null)
    {
        parent::__construct($connection, $endpoint);
    }
}
