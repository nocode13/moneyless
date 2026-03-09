<?php

namespace App\Http\Controllers;

use App\Exceptions\NotFoundException;
use App\Http\Resources\WalletResource;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Container\Attributes\CurrentUser;

class WalletController extends Controller
{
    public function __construct(
        private WalletService $walletService,
        #[CurrentUser] private User $user
    ) {}

    public function show(): WalletResource
    {
        $wallet = $this->user->wallet()->with('accounts.currency')->first();

        if (! $wallet) {
            throw new NotFoundException('Wallet not found');
        }

        return WalletResource::make($wallet);
    }

    public function create(): WalletResource
    {
        $wallet = $this->walletService->create($this->user);

        $wallet->loadMissing('accounts.currency');

        return WalletResource::make($wallet);
    }
}
