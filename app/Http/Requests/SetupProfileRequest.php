<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Step 1 of the first-run wizard.
 *
 * Deliberately NOT ClinicProfileRequest. That one requires `name` and can
 * require `id_prefix`, and the wizard asks for neither: both were set at
 * provisioning, the name is central, and the prefix is already stamped into
 * every identifier the clinic will ever mint. Reusing it would have forced the
 * wizard to re-ask for two things it must not touch.
 *
 * The rules that DO overlap are kept identical, so a value accepted here is
 * accepted in Settings afterwards.
 */
class SetupProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role middleware gates this route
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{7,}$/'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'consultation_fee' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'timezone' => ['required', 'string', Rule::in(timezone_identifiers_list())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'consultation_fee.required' => 'Enter a consultation fee. Use 0 if you do not charge one.',
            'phone.regex' => 'Enter a reachable phone number, e.g. 0803 123 4567.',
            'timezone.required' => 'Choose the timezone your clinic works in.',
        ];
    }
}
