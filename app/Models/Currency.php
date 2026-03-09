<?php

namespace App\Models;

use App\Enums\CurrencyType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'symbol',
    ];

    protected function casts(): array
    {
        return [
            'type' => CurrencyType::class,
        ];
    }

    /**
     * Get the accounts associated with the currency.
     *
     * @return HasMany<Account, $this>
     */
    protected function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }
}
