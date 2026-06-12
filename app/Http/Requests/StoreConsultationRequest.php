<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by route middleware + policy
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'chief_complaint' => ['required', 'string', 'max:1000'],
            'clinical_notes' => ['required', 'string', 'max:5000'],
            'diagnosis' => ['nullable', 'string', 'max:2000'],
            'plan' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required' => 'Please select a patient for this consultation.',
            'chief_complaint.required' => 'Record why the patient came in.',
            'clinical_notes.required' => 'Clinical notes are required.',
        ];
    }
}
