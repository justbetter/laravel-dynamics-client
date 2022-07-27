<?php

namespace JustBetter\DynamicsClient\Client;

use JustBetter\DynamicsClient\Exceptions\DynamicsException;
use SaintSystems\OData\ODataClient;

class ClientFactory
{
    public array $options = [];

    public string $url;

    public function __construct(public string $connection)
    {
        $config = config('dynamics.connections.'.$connection);

        if (! $config) {
            throw new DynamicsException(
                __('Connection ":connection" does not exist', ['connection' => $connection])
            );
        }

        $this
            ->options($config['options'])
            ->url($config['base_url'], $config['version'], "Company('{$config['company']}')")
            ->auth($config['username'], $config['password'], $config['auth'])
            ->header('Accept', 'application/json')
            ->header('Content-Type', 'application/json');
    }

    public static function make(string $connection): static
    {
        return new static($connection);
    }

    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function option(string $option, mixed $value): static
    {
        $this->options[$option] = $value;

        return $this;
    }

    public function headers(array $headers): static
    {
        $this->options['headers'] = $headers;

        return $this;
    }

    public function header(string $key, string $value): static
    {
        $this->options['headers'][$key] = $value;

        return $this;
    }

    public function etag(string $etag = null): static
    {
        $this->header('If-Match', 'W/"\''.$etag.'\'"');

        return $this;
    }

    public function url(string ...$url): static
    {
        $this->url = implode('/', $url);

        return $this;
    }

    public function auth(string $username, string $password, string $auth): static
    {
        $credentials = [
            $username,
            $password,
        ];

        if ($auth === 'ntlm') {
            $credentials[] = 'ntlm';
        }

        $this->option('auth', $credentials);

        return $this;
    }

    public function fabricate(): ODataClient
    {
        $httpProvider = new ClientHttpProvider();
        $httpProvider->setExtraOptions($this->options);

        return new ODataClient($this->url, null, $httpProvider);
    }
}
