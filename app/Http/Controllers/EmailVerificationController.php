<?php

namespace App\Http\Controllers;

use App\Services\AdminNotifier;
use App\Services\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends BaseController
{
    public function __construct(
        protected EmailVerificationService $emailVerificationService,
        protected AdminNotifier $adminNotifier,
    ) {}

    /**
     * Show the code-entry page (or skip if already verified).
     */
    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        return view('auth.verify-email');
    }

    /**
     * Check the submitted 6-digit code.
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ]);

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        if (! $this->emailVerificationService->verify($user, $request->input('code'))) {
            return back()->withErrors([
                'code' => 'That code is invalid or has expired. Please try again.',
            ]);
        }

        $this->adminNotifier->emailVerified($user);

        return redirect()->route('dashboard')->with('success', 'Email verified. Welcome to AfriChart EMR!');
    }

    /**
     * Re-send a fresh code.
     */
    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $this->emailVerificationService->sendCode($request->user());

        return back()->with('success', 'A new verification code has been sent to your email.');
    }
}
