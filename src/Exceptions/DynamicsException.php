<?php

namespace JustBetter\DynamicsClient\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;
use SaintSystems\OData\HttpRequestMessage;

class DynamicsException extends Exception
{
    public ?HttpRequestMessage $request = null;

    public ?Response $response = null;

    public function setRequest(HttpRequestMessage $request): static
    {
        $this->request = $request;

        return $this;
    }

    public function setResponse(Response $response): static
    {
        $this->response = $response;

        return $this;
    }
}
