<?php

namespace JustBetter\DynamicsClient\Client;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use JustBetter\DynamicsClient\Exceptions\DynamicsException;
use JustBetter\DynamicsClient\Exceptions\ModifiedException;
use JustBetter\DynamicsClient\Exceptions\NotFoundException;
use Psr\Http\Message\ResponseInterface;
use SaintSystems\OData\GuzzleHttpProvider;
use SaintSystems\OData\HttpRequestMessage;

class ClientHttpProvider extends GuzzleHttpProvider
{
    public function send(HttpRequestMessage $request): ResponseInterface
    {
        try {
            $options = $this->extra_options;

            if ($request->body !== null) {
                $options['body'] = $request->body;
            }

            $response = Http::send($request->method, $request->requestUri, $options);
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
        }
    }
}
