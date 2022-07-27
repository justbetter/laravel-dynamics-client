<?php

namespace JustBetter\DynamicsClient;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use JustBetter\DynamicsClient\Commands\TestConnection;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this
            ->registerConfig();
    }

    protected function registerConfig(): static
    {
        $this->mergeConfigFrom(__DIR__.'/../config/dynamics.php', 'dynamics');

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
