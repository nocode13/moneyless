<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\WalletService;
use Illuminate\Auth\Events\Verified;

class CreateWalletOnVerified
{
    public function __construct(private WalletService $walletService) {}

    public function handle(Verified $event): void
    {
        /** @var User $user */
        $user = $event->user;
        $this->walletService->create($user);
    }
}
