<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared validation for both lead forms (Book a Demo, Get Started).
 *
 * Public and unauthenticated, so it carries a honeypot alongside the route's
 * throttle:5,1. Nigerian numbers are accepted in the shapes people actually
 * type them — 0803…, +234803…, with spaces or dashes.
 */
class LeadRequest extends FormRequest
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
            'clinic_name' => ['required', 'string', 'min:2', 'max:255'],
            'contact_name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{7,}$/'],
            'city' => ['nullable', 'string', 'max:255'],
            'doctors' => ['nullable', 'integer', 'min:0', 'max:999'],
            'message' => ['nullable', 'string', 'max:2000'],

            // Demo only. Free-text would be a scheduling guessing game, so both
            // are constrained to the options the form actually offers.
            'preferred_time' => ['nullable', 'string', Rule::in(['morning', 'afternoon', 'evening'])],
            'heard_from' => ['nullable', 'string', Rule::in(['search', 'referral', 'social', 'event', 'other'])],

            // Sign-up only. `plan` arrives from the /pricing CTA and is what
            // provisioning will read later, so it must match a real tier.
            'plan' => ['nullable', 'string', Rule::in(['starter', 'clinic', 'group'])],

            /*
             * Consent, sign-up only. The POST routes are unnamed, so this keys
             * off the path rather than the route name. Not a column — the
             * controller strips it before create(); what is stored is the
             * timestamped row itself, which is the record that consent was
             * given at that moment.
             */
            'terms' => [$this->is('signup') ? 'accepted' : 'nullable'],

            // Honeypot: a real person never sees or fills this.
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'clinic_name.required' => 'Please tell us your clinic name.',
            'contact_name.required' => 'Please tell us your name.',
            'phone.regex' => 'Enter a reachable phone number, e.g. 0803 123 4567.',
            'doctors.integer' => 'Enter the number of doctors as a whole number.',
            'website.prohibited' => 'Something went wrong. Please try again.',
            'terms.accepted' => 'Please confirm you have read the privacy policy and terms.',
        ];
    }
}
