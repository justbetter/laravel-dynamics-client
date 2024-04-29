<?php

namespace JustBetter\DynamicsClient\Actions;

use Illuminate\Support\Facades\Http;
use JustBetter\DynamicsClient\Data\TokenData;

class ResolveTokenData
{
    public function resolve(string $connection, array $config): TokenData
    {
        $cacheKey = 'dynamics-client:token:' . $connection;

        if ($tokenData = cache()->get($cacheKey)) {
            return TokenData::of(decrypt($tokenData));
        }

        $payload = [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri' => $config['redirect_uri'],
            'grant_type' => $config['grant_type'],
            'scope' => $config['scope'],
        ];

        $response = Http::asMultipart()
            ->post($config['redirect_uri'], $payload)
            ->throw();

        $tokenData = TokenData::of(
            $response->json()
        );

        $expiresAt = now()->addSeconds($tokenData->expiresIn());

        cache()->remember($cacheKey, $expiresAt, fn (): string => encrypt($tokenData->toArray()));

        return $tokenData;
    }
}
