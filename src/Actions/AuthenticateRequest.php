<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Actions;

use Illuminate\Http\Client\PendingRequest;
use JustBetter\DynamicsClient\Client\Dynamics;
use JustBetter\DynamicsClient\Contracts\AuthenticatesRequest;
use JustBetter\DynamicsClient\Contracts\OAuth\RequestsAccessToken;

class AuthenticateRequest implements AuthenticatesRequest
{
    public function __construct(
        protected RequestsAccessToken $token
    ) {}

    public function authenticate(PendingRequest $request, Dynamics $dynamics): PendingRequest
    {
        $token = $this->token->request($dynamics);

        return $request->withToken($token->accessToken(), $token->tokenType());
    }

    public static function bind(): void
    {
        app()->singleton(AuthenticatesRequest::class, static::class);
    }
}
