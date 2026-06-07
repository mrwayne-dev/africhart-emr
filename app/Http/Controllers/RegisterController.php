<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Services\AdminNotifier;
use App\Services\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends BaseController
{
    public function __construct(
        protected EmailVerificationService $emailVerificationService,
        protected AdminNotifier $adminNotifier,
    ) {}

    public function show(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Role is authoritative from the validated invite code/role pairing.
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
        ]);

        $this->emailVerificationService->sendCode($user);
        $this->adminNotifier->staffRegistered($user);

        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}
