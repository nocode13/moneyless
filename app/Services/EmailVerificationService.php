<?php

namespace App\Services;

use App\Exceptions\EmailAlreadyVerifiedException;
use App\Exceptions\InvalidVerificationLinkException;
use App\Models\User;

final class EmailVerificationService
{
    public function verify(User $user, string $hash): void
    {
        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            throw new InvalidVerificationLinkException();
        }

        $this->checkIfAlreadyVerified($user);

        $user->markEmailAsVerified();
    }

    public function resend(User $user): void
    {
        $this->checkIfAlreadyVerified($user);

        $user->sendEmailVerificationNotification();
    }

    private function checkIfAlreadyVerified(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            throw new EmailAlreadyVerifiedException();
        }
    }
}
