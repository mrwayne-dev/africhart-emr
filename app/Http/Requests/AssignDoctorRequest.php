<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignDoctorRequest extends FormRequest
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
            'assigned_doctor_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', UserRole::Doctor->value),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'assigned_doctor_id.required' => 'Please choose a doctor to assign.',
        ];
    }
}
