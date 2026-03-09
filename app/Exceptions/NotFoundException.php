<?php

namespace App\Exceptions;

final class NotFoundException extends ApiException
{
    public function __construct(?string $message)
    {
        parent::__construct($message ?? 'Unknown error.', 404);
    }
}
