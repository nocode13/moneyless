<?php

namespace App\Exceptions;

final class InvalidCredentialsException extends ApiException
{
    public function __construct()
    {
        parent::__construct('Invalid email or password.', 401);
    }
}
