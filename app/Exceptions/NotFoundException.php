<?php

namespace App\Exceptions;

final class NotFoundException extends ApiException
{
    public function __construct(?string $message = null)
    {
        parent::__construct($message ?? 'Unknown error.', 404);
    }
}
