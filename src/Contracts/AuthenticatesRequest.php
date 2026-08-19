<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Contracts;

use Illuminate\Http\Client\PendingRequest;
use JustBetter\DynamicsClient\Client\Dynamics;

interface AuthenticatesRequest
{
    public function authenticate(PendingRequest $request, Dynamics $dynamics): PendingRequest;
}
