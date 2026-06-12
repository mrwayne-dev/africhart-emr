<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
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
            'tax' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
