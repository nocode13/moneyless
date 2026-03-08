<?php

namespace App\Exceptions;

final class EmailNotVerifiedException extends ApiException
{
    public function __construct()
    {
        parent::__construct('Email not verified.', 403);
    }
}
