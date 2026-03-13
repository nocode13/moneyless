<?php

namespace App\Http\Controllers;

use App\Exceptions\WalletNotFoundException;
use App\Http\Resources\WalletResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

class WalletController extends Controller
{

    public function show(#[CurrentUser] User $user): WalletResource
    {
        $wallet = $user->wallet()->with('accounts.currency')->first();

        if (! $wallet) {
            throw new WalletNotFoundException();
        }

        return WalletResource::make($wallet);
    }
}
