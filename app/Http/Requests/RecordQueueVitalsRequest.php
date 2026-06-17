<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordQueueVitalsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by route middleware
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'temperature' => ['nullable', 'numeric', 'between:30,45'],
            'blood_pressure' => ['nullable', 'string', 'max:10', 'regex:/^\d{2,3}\/\d{2,3}$/'],
            'pulse_rate' => ['nullable', 'integer', 'between:20,250'],
            'weight' => ['nullable', 'numeric', 'between:0,500'],
            'height' => ['nullable', 'numeric', 'between:0,300'],
            'vitals_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'blood_pressure.regex' => 'Enter blood pressure as systolic/diastolic, e.g. 120/80.',
            'temperature.between' => 'Temperature should be between 30 and 45 °C.',
        ];
    }
}
