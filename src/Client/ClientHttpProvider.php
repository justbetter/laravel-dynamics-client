<?php

namespace JustBetter\DynamicsClient\Client;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use JustBetter\DynamicsClient\Exceptions\DynamicsException;
use JustBetter\DynamicsClient\Exceptions\ModifiedException;
use JustBetter\DynamicsClient\Exceptions\NotFoundException;
use JustBetter\DynamicsClient\Exceptions\UnreachableException;
use SaintSystems\OData\GuzzleHttpProvider;
use SaintSystems\OData\HttpRequestMessage;

class ClientHttpProvider extends GuzzleHttpProvider
{
    public function send(HttpRequestMessage $request): mixed
    {
        try {
            return parent::send($request);
        } catch (RequestException $exception) {
            $message = $exception->hasResponse()
                ? $exception->getResponse()?->getBody()?->getContents()
                : $exception->getMessage();

            $code = $exception->getCode();

            /** @var class-string<DynamicsException> $mapping */
            $mapping = match ($code) {
                404 => NotFoundException::class,
                412 => ModifiedException::class,
                default => DynamicsException::class,
            };

            /** @var DynamicsException $dynamicsException */
            $dynamicsException = new $mapping($message, $code, $exception);

            throw $dynamicsException->setRequest($request);
        } catch (ConnectException $exception) {
            throw (new UnreachableException($exception->getMessage(), $exception->getCode(), $exception))
                ->setRequest($request);
        } catch (TransferException $exception) {
            throw (new DynamicsException($exception->getMessage(), $exception->getCode(), $exception))
                ->setRequest($request);
        }
    }
}
