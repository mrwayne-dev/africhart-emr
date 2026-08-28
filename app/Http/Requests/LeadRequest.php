<?php

namespace App\Http\Requests;

use App\Rules\UsableClinicSubdomain;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared validation for the three lead forms (Contact, Book a Demo, Get Started).
 *
 * Public and unauthenticated, so it carries a honeypot alongside the route's
 * throttle:5,1. Nigerian numbers are accepted in the shapes people actually
 * type them — 0803…, +234803…, with spaces or dashes.
 *
 * The POST routes are unnamed, so the per-form rules key off the path.
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
        /*
         * Contact is a general enquiry, so it inverts two rules: the sender may
         * not belong to a clinic at all (clinic name optional), and the message
         * IS the enquiry (required). On the other two forms the message is a
         * nice-to-have and the clinic is the whole point.
         */
        $isContact = $this->is('contact');

        return [
            /*
             * On sign-up the clinic name is not just a label — it becomes the
             * clinic's web address, and the form shows the visitor that address
             * as they type. So the name is validated against what it would
             * produce (ARCHITECTURE §3.3/§8.5): the form previewed `api` or
             * `admin` quite happily before this.
             *
             * Demo and Contact are conversations, not provisioning, so the name
             * there is only ever a label and the rule would be noise.
             */
            'clinic_name' => array_filter([
                $isContact ? 'nullable' : 'required', 'string', 'min:2', 'max:255',
                $this->is('signup') ? new UsableClinicSubdomain : null,
            ]),
            'contact_name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{7,}$/'],
            /*
             * Required on sign-up. We only onboard clinics in Port Harcourt at
             * the moment, so where the clinic is decides whether we can take it
             * on at all — that is a qualifying answer, not a nice-to-have.
             * Demo and Contact keep it optional: those are conversations, and
             * the question can be asked in one.
             */
            'city' => [$this->is('signup') ? 'required' : 'nullable', 'string', 'max:255'],
            'doctors' => ['nullable', 'integer', 'min:0', 'max:999'],
            'message' => [$isContact ? 'required' : 'nullable', 'string', 'max:2000'],

            // Demo only. Free-text would be a scheduling guessing game, so both
            // are constrained to the options the form actually offers.
            'preferred_time' => ['nullable', 'string', Rule::in(['morning', 'afternoon', 'evening'])],
            'heard_from' => ['nullable', 'string', Rule::in(['search', 'referral', 'social', 'event', 'other'])],

            // Sign-up only. `plan` arrives from the /pricing CTA and is what
            // provisioning will read later, so it must match a real tier.
            'plan' => ['nullable', 'string', Rule::in(['starter', 'clinic', 'group'])],

            /*
             * Consent, sign-up only. Not a column — the controller strips it
             * before create(); what is stored is the timestamped row itself,
             * which is the record that consent was given at that moment.
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
            'message.required' => 'Tell us what you need — a sentence is plenty.',
            'city.required' => 'Which city is the clinic in?',
            'phone.regex' => 'Enter a reachable phone number, e.g. 0803 123 4567.',
            'doctors.integer' => 'Enter the number of doctors as a whole number.',
            'website.prohibited' => 'Something went wrong. Please try again.',
            'terms.accepted' => 'Please confirm you have read the privacy policy and terms.',
        ];
    }
}
