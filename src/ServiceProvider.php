<?php

namespace JustBetter\DynamicsClient;

use JustBetter\DynamicsClient\Client\ClientFactory;
use JustBetter\DynamicsClient\Commands\TestConnection;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use JustBetter\DynamicsClient\Contracts\ClientFactoryContract;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this
            ->registerConfig()
            ->bindResolvers();
    }

    protected function registerConfig(): static
    {
        $this->mergeConfigFrom(__DIR__.'/../config/dynamics.php', 'dynamics');

        return $this;
    }

    protected function bindResolvers(): static
    {
        $this->app->bind(ClientFactoryContract::class, ClientFactory::class);

        return $this;
    }

    public function boot(): void
    {
        $this
            ->bootConfig()
            ->bootCommands();
    }

    protected function bootConfig(): static
    {
        $this->publishes([
            __DIR__.'/../config/dynamics.php' => config_path('dynamics.php'),
        ], 'config');

        return $this;
    }

    protected function bootCommands(): static
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                TestConnection::class,
            ]);
        }

        return $this;
    }
}
