<?php

use App\Enums\CurrencyType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('type', CurrencyType::cases());
            $table->string('symbol');
            $table->timestamps();
        });

        $now = now();

        DB::table('currencies')->insert([
            ['code' => 'USD', 'name' => 'US Dollar',       'type' => CurrencyType::FIAT->value,   'symbol' => '$', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'EUR', 'name' => 'Euro',             'type' => CurrencyType::FIAT->value,   'symbol' => '€', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'UZS', 'name' => 'Uzbekistani Som',  'type' => CurrencyType::FIAT->value,   'symbol' => 'C', 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'BTC', 'name' => 'Bitcoin',          'type' => CurrencyType::CRYPTO->value, 'symbol' => '₿', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
