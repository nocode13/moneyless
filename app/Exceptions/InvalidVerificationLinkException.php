<?php

namespace App\Exceptions;

final class InvalidVerificationLinkException extends ApiException
{
    public function __construct()
    {
        parent::__construct('Invalid or expired verification link.', 403);
    }
}
