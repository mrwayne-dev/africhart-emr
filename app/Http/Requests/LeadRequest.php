<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
        ];
    }
}
