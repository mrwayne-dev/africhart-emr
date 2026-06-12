<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'min:2'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['required', Rule::in(array_column(UserRole::cases(), 'value'))],
            'invite_code' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $role = $this->input('role');
                    $expected = config('registration.codes')[$role] ?? null;

                    if (! $expected || ! hash_equals($expected, (string) $value)) {
                        $fail('Invalid invite code for the selected role.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.confirmed' => 'The passwords do not match.',
            'email.unique' => 'An account with this email already exists.',
        ];
    }
}
