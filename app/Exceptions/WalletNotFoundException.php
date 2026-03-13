<?php

namespace App\Exceptions;

final class WalletNotFoundException extends ApiException
{
    public function __construct()
    {
        parent::__construct('Wallet not found', 404);
    }
}
