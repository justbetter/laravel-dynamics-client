<?php

namespace JustBetter\DynamicsClient\Client;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use JustBetter\DynamicsClient\Contracts\Availability\ChecksAvailability;
use JustBetter\DynamicsClient\Events\DynamicsResponseEvent;
use JustBetter\DynamicsClient\Events\DynamicsTimeoutEvent;
use JustBetter\DynamicsClient\Exceptions\DynamicsException;
use JustBetter\DynamicsClient\Exceptions\ModifiedException;
use JustBetter\DynamicsClient\Exceptions\NotFoundException;
use JustBetter\DynamicsClient\Exceptions\UnavailableException;
use Psr\Http\Message\ResponseInterface;
use SaintSystems\OData\GuzzleHttpProvider;
use SaintSystems\OData\HttpRequestMessage;

class ClientHttpProvider extends GuzzleHttpProvider
{
    public function __construct(protected string $connection)
    {
        parent::__construct();
    }

    public function send(HttpRequestMessage $request): ResponseInterface
    {
        $throw = config('dynamics.connections.'.$this->connection.'.availability.throw', false);

        if ($throw && ! $this->available()) {
            throw new UnavailableException('The Dynamics connection "'.$this->connection.'" is currently unavailable.');
        }

        try {
            $options = $this->extra_options;

            if ($request->body !== null) {
                $options['body'] = $request->body;
            }

            $response = Http::send($request->method, $request->requestUri, $options);
            DynamicsResponseEvent::dispatch($response, $this->connection);

            $response->throw();

            return $response->toPsrResponse();
        } catch (RequestException $exception) {
            $message = $exception->getMessage();
            $code = $exception->getCode();

            $mapping = match ($code) {
                404 => NotFoundException::class,
                412 => ModifiedException::class,
                default => DynamicsException::class,
            };

            /** @var DynamicsException $dynamicsException */
            $dynamicsException = new $mapping($message, $code, $exception);

            throw $dynamicsException
                ->setRequest($request)
                ->setResponse($exception->response);
        } catch (ConnectionException $e) {
            DynamicsTimeoutEvent::dispatch($this->connection);

            throw $e;
        }
    }

    protected function available(): bool
    {
        /** @var ChecksAvailability $checker */
        $checker = app(ChecksAvailability::class);

        return $checker->check($this->connection);
    }
}
