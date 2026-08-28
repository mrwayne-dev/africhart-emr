<?php

namespace App\Services;

use App\Models\Staff;
use App\Notifications\EmailVerificationCode;

class EmailVerificationService
{
    /**
     * Generate a fresh 6-digit code, store it on the user, and email it.
     */
    public function sendCode(Staff $user): void
    {
        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'email_verification_code' => $code,
            'email_verification_code_expires_at' => now()->addMinutes(
                config('registration.verification_code_ttl', 10)
            ),
        ])->save();

        $user->notify(new EmailVerificationCode($code, tenant('name')));
    }

    /**
     * Check a submitted code. On success, mark the user verified and clear the code.
     */
    public function verify(Staff $user, string $code): bool
    {
        if ($user->email_verification_code === null
            || $user->email_verification_code_expires_at === null
            || $user->email_verification_code_expires_at->isPast()) {
            return false;
        }

        if (! hash_equals($user->email_verification_code, $code)) {
            return false;
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'email_verification_code' => null,
            'email_verification_code_expires_at' => null,
        ])->save();

        return true;
    }
}
