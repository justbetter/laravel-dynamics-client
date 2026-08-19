<?php

declare(strict_types=1);

namespace JustBetter\DynamicsClient\Data;

use Illuminate\Support\Collection;
use JustBetter\DynamicsClient\Exceptions\DynamicsException;

/**
 * @property string $base_url
 * @property array<string, ?string> $parameters
 * @property array<string, ?string> $companies
 * @property string $client_id
 * @property string $client_secret
 * @property string $token_url
 * @property string $scope
 * @property string $grant_type
 * @property int $page_size
 * @property int $timeout
 * @property int $connect_timeout
 * @property array<string, mixed> $availability
 *
 * @extends Data<string, mixed>
 */
class Connection extends Data
{
    /** @var array<string, string> */
    protected array $rules = [
        'base_url' => 'required|string',
        'parameters' => 'required|array',
        'companies' => 'present|array',
        'client_id' => 'required|string',
        'client_secret' => 'required|string',
        'token_url' => 'required|string',
        'scope' => 'required|string',
        'grant_type' => 'required|in:client_credentials',
        'page_size' => 'required|integer',
        'timeout' => 'required|integer',
        'connect_timeout' => 'required|integer',
    ];

    public function company(string $name): string
    {
        /** @var Collection<string, ?string> $companies */
        $companies = collect($this->companies);

        throw_unless($companies->has($name), DynamicsException::class, 'Company "'.$name.'" not found in the connection configuration.');

        $id = $companies->get($name);

        throw_if(blank($id), DynamicsException::class, 'Company "'.$name.'" has no ID configured in the connection configuration.');

        return $id;
    }
}
