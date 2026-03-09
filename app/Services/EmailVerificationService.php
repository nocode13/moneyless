<?php

namespace App\Services;

use App\Exceptions\EmailAlreadyVerifiedException;
use App\Exceptions\InvalidVerificationLinkException;
use App\Exceptions\TooManyRequestsException;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Cache;

final class EmailVerificationService
{
    public function __construct(private WalletService $walletService) {}

    public function verify(User $user, string $hash): void
    {
        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            throw new InvalidVerificationLinkException();
        }

        if ($user->hasVerifiedEmail()) {
            throw new EmailAlreadyVerifiedException();
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            $this->walletService->create($user);
        }
    }

    public function resend(User $user): void
    {
        $lock = Cache::lock("verification_resend_{$user->id}", 20);

        if (! $lock->get()) {
            throw new TooManyRequestsException();
        }

        if ($user->hasVerifiedEmail()) {
            throw new EmailAlreadyVerifiedException();
        }

        $user->sendEmailVerificationNotification();
    }
}
