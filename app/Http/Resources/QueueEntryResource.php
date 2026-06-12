<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QueueEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'queue_number' => $this->queue_number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'reason' => $this->reason,
            'patient' => new PatientResource($this->whenLoaded('patient')),
            'assigned_doctor' => new UserResource($this->whenLoaded('assignedDoctor')),
            'checked_in_by' => new UserResource($this->whenLoaded('checkedInBy')),
            'checked_in_at' => $this->checked_in_at?->toISOString(),
            'seen_at' => $this->seen_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
        ];
    }
}
