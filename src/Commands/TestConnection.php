<?php

namespace JustBetter\DynamicsClient\Commands;

use Illuminate\Console\Command;
use JustBetter\DynamicsClient\Client\ClientFactory;
use SaintSystems\OData\ODataResponse;

class TestConnection extends Command
{
    protected $signature = 'dynamics:connect {connection?}';

    protected $description = 'Test the connection to Dynamics';

    public function handle(): int
    {
        $connection = $this->argument('connection') ?? config('dynamics.connection');

        $client = ClientFactory::make($connection)->fabricate();
        $client->setEntityReturnType(false);

        /** @var ODataResponse $response */
        $response = $client->get('');

        $company = $response->getBody();

        $this->info('Successfully connected to company "'.$company['Name'].'"');

        return static::SUCCESS;
    }
}
