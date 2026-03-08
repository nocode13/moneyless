<?php

namespace App\Exceptions;

final class EmailAlreadyVerifiedException extends ApiException
{
    public function __construct()
    {
        parent::__construct('Email already verified.', 409);
    }
}
