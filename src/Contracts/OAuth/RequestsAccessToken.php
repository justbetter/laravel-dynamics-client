<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Contracts\OAuth;

use JustBetter\DynamicsClient\Client\Dynamics;
use JustBetter\DynamicsClient\Data\TokenData;

interface RequestsAccessToken
{
    public function request(Dynamics $dynamics): TokenData;
}
