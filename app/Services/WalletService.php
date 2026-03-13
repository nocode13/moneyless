<?php

namespace App\Services;

use App\Exceptions\TooManyRequestsException;
use App\Exceptions\WalletAlreadyExistsException;
use App\Models\Account;
use App\Models\Currency;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class WalletService
{
    public function create(User $user): Wallet
    {
        $lock = Cache::lock("create_wallet_{$user->id}", 5);
        if (! $lock->get()) {
            throw new TooManyRequestsException();
        }

        try {
            DB::beginTransaction();

            if ($user->wallet()->exists()) {
                throw new WalletAlreadyExistsException();
            }

            $wallet = Wallet::create([
                'user_id' => $user->id,
            ]);

            $timestamp = now();
            $accounts = [];

            foreach (Currency::pluck('id') as $id) {
                $accounts[] = [
                    'currency_id' => $id,
                    'wallet_id' => $wallet->id,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            Account::insert($accounts);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            if ($e instanceof UniqueConstraintViolationException) {
                throw new WalletAlreadyExistsException();
            }

            throw $e;
        } finally {
            $lock->release();
        }

        return $wallet;
    }
}
