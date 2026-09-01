<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The clinic's logo upload.
 *
 * Validated as an IMAGE with an explicit extension list rather than by mime
 * type alone: `image` accepts SVG on some stacks, and an SVG is a document that
 * can carry script. This logo is rendered on an invoice a patient may open, so
 * the format list is the allow-list and it is deliberately short.
 */
class BrandingRequest extends FormRequest
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
            'logo' => [
                'required',
                'image',
                'mimes:png,jpg,jpeg,webp',
                'max:2048',              // kilobytes — a letterhead, not a photograph
                'dimensions:max_width=2000,max_height=2000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'logo.required' => 'Choose an image file to upload.',
            'logo.image' => 'That file is not an image.',
            'logo.mimes' => 'Use a PNG, JPG or WebP. SVG is not accepted, because it can carry script.',
            'logo.max' => 'Keep the logo under 2 MB — it only ever prints at letterhead size.',
            'logo.dimensions' => 'That image is unusually large. 2000×2000 pixels is plenty for a letterhead.',
        ];
    }
}
