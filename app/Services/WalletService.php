<?php

namespace App\Services;

use App\Exceptions\TooManyRequestsException;
use App\Exceptions\WalletAlreadyExistsException;
use App\Models\Currency;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function create(User $user): Wallet
    {
        $lock = Cache::lock("create_wallet_{$user->id}", 5);
        $wallet = $user->wallet;

        try {
            if (! $lock->get()) {
                throw new TooManyRequestsException();
            }

            if ($wallet) {
                throw new WalletAlreadyExistsException();
            }

            return DB::transaction(function () use ($user) {
                $wallet = Wallet::create([
                    'user_id' => $user->id,
                ]);

                Currency::all()->each(function (Currency $currency) use ($wallet) {
                    $wallet->accounts()->create([
                        'currency_id' => $currency->id,
                        'amount' => 0,
                    ]);
                });

                return $wallet;
            });
        } finally {
            $lock->release();
        }
    }
}
