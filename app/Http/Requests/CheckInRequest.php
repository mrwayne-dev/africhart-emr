<?php

namespace App\Http\Requests;

use App\Enums\StaffRole;
use App\Models\Staff;
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
                /*
                 * Staff::class, not the string 'staff'. Laravel resolves a
                 * model class to its table, and the class reference is what
                 * makes the next rename safe: this rule previously named
                 * `users` as a string literal, so when that table was renamed
                 * the grep for `User::` / `App\Models\User` matched nothing
                 * and the rule survived the rename pointing at a table that no
                 * longer existed. Every request that selected a doctor 500'd.
                 */
                Rule::exists(Staff::class, 'id')->where('role', StaffRole::Doctor->value),
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
