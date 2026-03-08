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
        }

        Cache::forget($this->resendThrottleKey($user));
    }

    public function resend(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            throw new EmailAlreadyVerifiedException();
        }

        $key = $this->resendThrottleKey($user);

        if (Cache::has($key)) {
            throw new TooManyRequestsException();
        }

        $user->sendEmailVerificationNotification();

        Cache::put($key, true, config('auth.verification.resend_throttle', 20));
    }

    private function resendThrottleKey(User $user): string
    {
        return "verification_resend:{$user->id}";
    }
}
