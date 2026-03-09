<?php

namespace App\Enums;

enum CurrencyType: string
{
    case FIAT = 'FIAT';
    case CRYPTO = 'CRYPTO';
}
