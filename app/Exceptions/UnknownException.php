<?php

namespace App\Exceptions;

final class UnknownException extends ApiException
{
    public function __construct()
    {
        parent::__construct('Unknown error.', 500);
    }
}
