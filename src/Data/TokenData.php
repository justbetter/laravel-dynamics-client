<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Data;

/**
 * @property string $token_type
 * @property int $expires_in
 * @property string $access_token
 *
 * @extends Data<string, mixed>
 */
class TokenData extends Data
{
    /** @var array<string, string> */
    protected array $rules = [
        'token_type' => 'required|string',
        'expires_in' => 'required|integer',
        'access_token' => 'required|string',
    ];

    public function tokenType(): string
    {
        return $this->token_type;
    }

    public function expiresIn(): int
    {
        return $this->expires_in;
    }

    public function accessToken(): string
    {
        return $this->access_token;
    }
}
