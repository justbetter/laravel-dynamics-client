<?php

namespace JustBetter\DynamicsClient\Exceptions;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NotFoundException extends DynamicsException {

    public function render(Request $request): Response
    {
        return response(null, 404);
    }
}
