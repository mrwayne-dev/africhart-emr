<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'full_name' => $this->full_name,
            'date_of_birth' => $this->date_of_birth->format('Y-m-d'),
            'age' => $this->age,
            'phone' => $this->phone,
            'blood_group' => $this->blood_group->value,
            'allergies' => $this->allergies,
            'registered_by' => new UserResource($this->whenLoaded('registeredBy')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
