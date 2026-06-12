<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckInRequest extends FormRequest
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
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'assigned_doctor_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', UserRole::Doctor->value),
            ],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required' => 'Please select a patient to check in.',
            'assigned_doctor_id.exists' => 'The selected doctor is not valid.',
        ];
    }
}
