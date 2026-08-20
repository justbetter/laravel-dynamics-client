<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Tests;

use Illuminate\Support\Facades\Http;
use JustBetter\DynamicsClient\Client\Dynamics;
use JustBetter\DynamicsClient\ServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        Http::preventStrayRequests();

        Dynamics::fake();
    }
}
