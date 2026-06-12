<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'consultation_id' => $this->consultation_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'chief_complaint' => $this->chief_complaint,
            'clinical_notes' => $this->clinical_notes,
            'diagnosis' => $this->diagnosis,
            'plan' => $this->plan,
            'vitals' => [
                'temperature' => $this->temperature,
                'blood_pressure' => $this->blood_pressure,
                'pulse_rate' => $this->pulse_rate,
                'weight' => $this->weight,
                'height' => $this->height,
                'bmi' => $this->bmi,
                'notes' => $this->vitals_notes,
            ],
            'patient' => new PatientResource($this->whenLoaded('patient')),
            'doctor' => new UserResource($this->whenLoaded('doctor')),
            'prescriptions' => PrescriptionResource::collection($this->whenLoaded('prescriptions')),
            'invoice' => new InvoiceResource($this->whenLoaded('invoice')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
