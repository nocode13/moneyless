<?php

namespace App\Http\Controllers;

use App\Http\Resources\WalletResource;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Container\Attributes\CurrentUser;

class WalletController extends Controller
{
    public function __construct(private WalletService $walletService) {}

    public function show(#[CurrentUser] User $user): WalletResource
    {
        $wallet = $user->wallet()->with('accounts.currency')->first();

        return WalletResource::make($wallet);
    }

    public function create(#[CurrentUser] User $user): WalletResource
    {
        $wallet = $this->walletService->create($user)->with('accounts.currency')->first();

        return WalletResource::make($wallet);
    }
}
