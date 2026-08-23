<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A clinic staff member, as the API renders them.
 *
 * Renamed with the model. This one is worth noting: it referenced User only by
 * NAME — no import, no User:: call — so it never appeared in the §8.1 inventory
 * of files touching the model, and a grep for the model would not have found
 * it. Renames leak through vocabulary, not just through imports.
 */
class StaffResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'role_label' => $this->role->label(),
        ];
    }
}
