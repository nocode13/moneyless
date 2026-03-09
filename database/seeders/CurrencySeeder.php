<?php

namespace Database\Seeders;

use App\Enums\CurrencyType;
use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar', 'type' => CurrencyType::FIAT, 'symbol' => '$'],
            ['code' => 'EUR', 'name' => 'Euro', 'type' => CurrencyType::FIAT, 'symbol' => '€'],
            ['code' => 'UZS', 'name' => 'Uzbekistani Som', 'type' => CurrencyType::FIAT, 'symbol' => 'C'],
            ['code' => 'BTC', 'name' => 'Bitcoin', 'type' => CurrencyType::CRYPTO, 'symbol' => '₿'],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                $currency,
            );
        }
    }
}
