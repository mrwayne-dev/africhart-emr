<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'consultation_id' => $this->consultation_id,
            'patient_id' => $this->patient_id,
            'medication_name' => $this->medication_name,
            'dosage' => $this->dosage,
            'frequency' => $this->frequency,
            'duration' => $this->duration,
            'route' => $this->route->value,
            'route_label' => $this->route->label(),
            'instructions' => $this->instructions,
            'quantity' => $this->quantity,
            'prescribed_by' => new UserResource($this->whenLoaded('prescribedBy')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
