<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Actions;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use JustBetter\DynamicsClient\Client\Dynamics;
use JustBetter\DynamicsClient\Contracts\AuthenticatesRequest;
use JustBetter\DynamicsClient\Contracts\BuildsRequest;

class BuildRequest implements BuildsRequest
{
    public function __construct(
        protected AuthenticatesRequest $request
    ) {}

    public function build(Dynamics $dynamics): PendingRequest
    {
        $config = Dynamics::config($dynamics->getConnection());

        $pendingRequest = Http::baseUrl($dynamics->baseUrl())
            ->timeout($config->timeout)
            ->connectTimeout($config->connect_timeout)
            ->acceptJson()
            ->asJson()
            ->withHeaders($dynamics->getHeaders());

        return $this->request->authenticate($pendingRequest, $dynamics);
    }

    public static function bind(): void
    {
        app()->singleton(BuildsRequest::class, static::class);
    }
}
