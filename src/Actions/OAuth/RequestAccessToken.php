<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Actions\OAuth;

use Illuminate\Support\Facades\Http;
use JustBetter\DynamicsClient\Client\Dynamics;
use JustBetter\DynamicsClient\Contracts\OAuth\RequestsAccessToken;
use JustBetter\DynamicsClient\Data\Connection;
use JustBetter\DynamicsClient\Data\TokenData;

class RequestAccessToken implements RequestsAccessToken
{
    public const string CACHE_KEY = 'dynamics-client:token:';

    public function request(Dynamics $dynamics): TokenData
    {
        $config = Dynamics::config($dynamics->getConnection());
        $tokenUrl = $dynamics->tokenUrl();
        $cacheKey = $this->cacheKey($dynamics->getConnection(), $tokenUrl, $config);

        $cached = cache()->get($cacheKey);

        if (is_string($cached)) {
            /** @var array<string, mixed> $attributes */
            $attributes = decrypt($cached);

            return TokenData::make($attributes);
        }

        $response = Http::asForm()
            ->acceptJson()
            ->timeout($config->timeout)
            ->connectTimeout($config->connect_timeout)
            ->post($tokenUrl, [
                'client_id' => $config->client_id,
                'client_secret' => $config->client_secret,
                'scope' => $config->scope,
                'grant_type' => $config->grant_type,
            ])
            ->throw();

        /** @var array<string, mixed> $json */
        $json = $response->json();

        $token = TokenData::make([
            'token_type' => $json['token_type'] ?? null,
            'expires_in' => isset($json['expires_in']) ? (int) $json['expires_in'] : null,
            'access_token' => $json['access_token'] ?? null,
        ])->validate();

        cache()->put($cacheKey, encrypt($token->toArray()), $token->expiresIn() - 60); // Cache the token for a minute less than its expiration time

        return $token;
    }

    protected function cacheKey(string $connection, string $tokenUrl, Connection $config): string
    {
        return static::CACHE_KEY.$connection.':'.sha1(implode('|', [$tokenUrl, $config->scope, $config->client_id]));
    }

    public static function bind(): void
    {
        app()->singleton(RequestsAccessToken::class, static::class);
    }
}
