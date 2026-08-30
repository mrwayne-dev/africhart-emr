<?php

namespace App\Http\Requests;

use App\Enums\MedicationRoute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by route middleware + policy
    }

    /**
     * Accepts one or more medications under `items[]` so the doctor can add
     * several prescriptions in a single submit.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            /*
             * Catalogue-linked OR free text, by design.
             *
             * medication_id is optional and, when given, must be a real drug in
             * THIS clinic's catalogue — exists: runs on the tenant connection,
             * so it cannot reach another clinic's medications.
             *
             * medication_name stays required either way: it is what appears on
             * the prescription, and for an off-catalogue drug it is the only
             * record of what was prescribed.
             */
            'items.*.medication_id' => ['nullable', 'integer', 'exists:medications,id'],
            'items.*.medication_name' => ['required', 'string', 'max:255'],
            'items.*.dosage' => ['required', 'string', 'max:100'],
            'items.*.frequency' => ['required', 'string', 'max:100'],
            'items.*.duration' => ['required', 'string', 'max:100'],
            'items.*.route' => ['required', Rule::enum(MedicationRoute::class)],
            'items.*.instructions' => ['nullable', 'string', 'max:500'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.*.medication_name.required' => 'Medication name is required.',
            'items.*.dosage.required' => 'Dosage is required.',
            'items.*.frequency.required' => 'Frequency is required.',
            'items.*.duration.required' => 'Duration is required.',
        ];
    }
}
