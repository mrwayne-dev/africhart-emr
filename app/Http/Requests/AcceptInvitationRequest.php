<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Someone accepting an invitation.
 *
 * ⚠️ There is deliberately NO `role` rule and NO `email` rule here, and that is
 * the fix for the privilege escalation the old register flow carried.
 *
 * Both values come from the invitation record. Anything posted under those
 * names is never read, so adding `role=admin` to the form does nothing at all —
 * the controller does not consult the request for it. Validating role here,
 * even strictly, would mean the request was a source of it.
 */
class AcceptInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // the token is the credential; the controller verifies it
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.confirmed' => 'The passwords do not match.',
            'name.required' => 'Please enter your full name.',
        ];
    }
}
