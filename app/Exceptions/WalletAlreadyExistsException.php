<?php

namespace App\Exceptions;

final class WalletAlreadyExistsException extends ApiException
{
    public function __construct()
    {
        parent::__construct('Wallet already exists.', 409);
    }
}
