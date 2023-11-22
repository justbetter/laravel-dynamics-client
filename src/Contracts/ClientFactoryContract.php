<?php

namespace JustBetter\DynamicsClient\Contracts;

use SaintSystems\OData\ODataClient;

interface ClientFactoryContract
{
    public static function make(string $connection): static;

    public function etag(string $etag): static;

    public function fabricate(): ODataClient;
}