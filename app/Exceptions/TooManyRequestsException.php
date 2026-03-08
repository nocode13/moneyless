<?php

namespace App\Exceptions;

final class TooManyRequestsException extends ApiException
{
    public function __construct()
    {
        parent::__construct('Too many requests. Please try again later.', 429);
    }
}
