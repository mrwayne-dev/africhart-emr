<?php

namespace App\Http\Requests;

use App\Models\Clinic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The clinic's own profile. Admin-only; the route group enforces that.
 *
 * Two of these fields are CENTRAL, not tenant settings — `name` and
 * `id_prefix` live on `clinics`. See ClinicProfileController for why.
 */
class ClinicProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role:admin middleware gates the route
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],

            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{7,}$/'],
            'email' => ['nullable', 'string', 'email', 'max:255'],

            /*
             * Zero is allowed — a clinic that does not charge a separate
             * consultation fee is a real clinic, not a validation error. The
             * fallback to config only applies when the setting is UNSET; an
             * explicit 0 must survive.
             */
            'consultation_fee' => ['required', 'numeric', 'min:0', 'max:10000000'],

            'timezone' => ['required', 'string', Rule::in(timezone_identifiers_list())],

            /*
             * Only validated when the field was actually editable — see
             * ClinicProfileController::canEditPrefix(). A read-only input posts
             * nothing, so `sometimes` keeps a locked prefix out of the rules
             * rather than failing a form that never offered the field.
             *
             * Uniqueness is central and enforced by a UNIQUE index; this rule
             * turns that into a message instead of an integrity error.
             */
            'id_prefix' => [
                'sometimes', 'required', 'string', 'regex:/^[A-Z0-9]{2,12}$/',
                Rule::unique(Clinic::class, 'id_prefix')->ignore(tenant('id')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Your clinic needs a name — it appears on invoices and on every page your staff use.',
            'phone.regex' => 'Enter a reachable phone number, e.g. 0803 123 4567.',
            'consultation_fee.required' => 'Enter a consultation fee. Use 0 if you do not charge one.',
            'timezone.in' => 'Choose a timezone from the list.',
            'id_prefix.regex' => 'Use 2–12 uppercase letters or digits. This appears on every patient ID and invoice number.',
            'id_prefix.unique' => 'Another clinic already uses that prefix. Choose a different one.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('id_prefix')) {
            $this->merge(['id_prefix' => strtoupper(trim((string) $this->input('id_prefix')))]);
        }
    }
}
