<?php

namespace App\Http\Requests;

use App\Enums\BloodGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Role check is handled by middleware
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255', 'min:3'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^(\+234|0)[789]\d{9}$/', // Nigerian phone format
            ],
            'blood_group' => ['required', new Enum(BloodGroup::class)],
            'allergies' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid Nigerian phone number (e.g. 08031234567 or +2348031234567).',
            'date_of_birth.before_or_equal' => 'Date of birth cannot be in the future.',
            'full_name.min' => "Please enter the patient's full name.",
        ];
    }
}
