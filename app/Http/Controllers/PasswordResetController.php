<?php

namespace App\Http\Controllers;

use App\Http\Requests\NewPasswordRequest;
use App\Http\Requests\PasswordResetLinkRequest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetController extends BaseController
{
    /**
     * Show the "enter your email" form.
     */
    public function request(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Email a reset link.
     */
    public function email(PasswordResetLinkRequest $request): RedirectResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        // Always report success to avoid leaking which emails are registered.
        return back()->with('success', 'If that email is registered, a reset link is on its way.');
    }

    /**
     * Show the "set a new password" form.
     */
    public function reset(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    /**
     * Persist the new password.
     */
    public function update(NewPasswordRequest $request): RedirectResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PasswordReset) {
            return redirect()->route('login')->with('success', 'Password reset. You can now sign in.');
        }

        return back()->withErrors(['email' => __($status)])->onlyInput('email');
    }
}
