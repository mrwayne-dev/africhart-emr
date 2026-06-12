<?php

namespace App\Http\Requests;

use App\Enums\ConsultationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConsultationRequest extends FormRequest
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
            'chief_complaint' => ['required', 'string', 'max:1000'],
            'clinical_notes' => ['required', 'string', 'max:5000'],
            'diagnosis' => ['nullable', 'string', 'max:2000'],
            'plan' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(ConsultationStatus::class)],
        ];
    }
}
