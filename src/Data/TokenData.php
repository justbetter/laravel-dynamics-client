<?php

namespace JustBetter\DynamicsClient\Data;

class TokenData extends Data
{
    public array $rules = [
        'token_type' => 'required|string',
        'expires_in' => 'required|int',
        'ext_expires_in' => 'required|int',
        'access_token' => 'required|string',
    ];

    public function tokenType(): string
    {
        return $this['token_type'];
    }

    public function expiresIn(): int
    {
        return $this['expires_in'];
    }

    public function extExpiresIn(): int
    {
        return $this['ext_expires_in'];
    }

    public function accessToken(): string
    {
        return $this['access_token'];
    }
}
