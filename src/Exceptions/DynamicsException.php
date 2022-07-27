<?php

namespace JustBetter\DynamicsClient\Exceptions;

use Exception;
use SaintSystems\OData\HttpRequestMessage;

class DynamicsException extends Exception
{
    public ?HttpRequestMessage $request = null;

    public function setRequest(HttpRequestMessage $request): static
    {
        $this->request = $request;

        return $this;
    }
}
