<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Commands;

use Illuminate\Console\Command;
use JustBetter\DynamicsClient\Client\Dynamics;
use Throwable;

class ConnectionCommand extends Command
{
    protected $signature = 'dynamics:connect {connection?}';

    protected $description = 'Test the connection to Dynamics';

    public const string NAME_KEY = 'name';

    public function handle(Dynamics $dynamics): int
    {
        /** @var ?string $connection */
        $connection = $this->argument('connection');

        $connection ??= config()->string('dynamics.connection');

        try {
            $response = $dynamics->connection($connection)->get('')->throw();
        } catch (Throwable $throwable) {
            $this->error('Could not connect to "'.$connection.'": '.$throwable->getMessage());

            return static::FAILURE;
        }

        /** @var ?string $name */
        $name = $response->json(static::NAME_KEY);

        $this->info('Successfully connected to company "'.$name.'"');

        return static::SUCCESS;
    }
}
