<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use JustBetter\DynamicsClient\Actions\AuthenticateRequest;
use JustBetter\DynamicsClient\Actions\Availability\CheckAvailability;
use JustBetter\DynamicsClient\Actions\Availability\RegisterUnavailability;
use JustBetter\DynamicsClient\Actions\BuildRequest;
use JustBetter\DynamicsClient\Actions\OAuth\RequestAccessToken;
use JustBetter\DynamicsClient\Commands\ConnectionCommand;
use JustBetter\DynamicsClient\Events\DynamicsResponseEvent;
use JustBetter\DynamicsClient\Events\DynamicsTimeoutEvent;
use JustBetter\DynamicsClient\Listeners\ResponseAvailabilityListener;
use JustBetter\DynamicsClient\Listeners\TimeoutAvailabilityListener;
use Override;

class ServiceProvider extends BaseServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this
            ->registerConfig()
            ->registerActions();
    }

    protected function registerConfig(): static
    {
        $this->mergeConfigFrom(__DIR__.'/../config/dynamics.php', 'dynamics');

        return $this;
    }

    protected function registerActions(): static
    {
        BuildRequest::bind();
        AuthenticateRequest::bind();
        CheckAvailability::bind();
        RegisterUnavailability::bind();
        RequestAccessToken::bind();

        return $this;
    }

    public function boot(): void
    {
        $this
            ->bootConfig()
            ->bootCommands()
            ->bootListeners();
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
                ConnectionCommand::class,
            ]);
        }

        return $this;
    }

    protected function bootListeners(): static
    {
        Event::listen(DynamicsTimeoutEvent::class, TimeoutAvailabilityListener::class);
        Event::listen(DynamicsResponseEvent::class, ResponseAvailabilityListener::class);

        return $this;
    }
}
