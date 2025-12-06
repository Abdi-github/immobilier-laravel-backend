<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class LeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            '_id' => (string) $this->id,
            'property_id' => (string) $this->property_id,
            'property' => $this->when(
                $this->relationLoaded('property'),
                fn () => $this->property ? new PropertyResource($this->property) : null,
            ),
            'agency_id' => $this->agency_id ? (string) $this->agency_id : null,
            'agency' => $this->when(
                $this->relationLoaded('agency'),
                fn () => $this->agency ? new AgencyResource($this->agency) : null,
            ),
            'user_id' => $this->user_id ? (string) $this->user_id : null,
            'contact_first_name' => $this->contact_first_name,
            'contact_last_name' => $this->contact_last_name,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'preferred_contact_method' => $this->preferred_contact_method?->value,
            'preferred_language' => $this->preferred_language,
            'inquiry_type' => $this->inquiry_type->value,
            'message' => $this->message,
            'status' => $this->status->value,
            'priority' => $this->priority->value,
            'source' => $this->source->value,
            'assigned_to' => $this->assigned_to ? (string) $this->assigned_to : null,
            'assigned_user' => $this->when(
                $this->relationLoaded('assignedTo'),
                fn () => $this->assignedTo ? new UserResource($this->assignedTo) : null,
            ),
            'viewing_scheduled_at' => $this->viewing_scheduled_at?->toISOString(),
            'follow_up_date' => $this->follow_up_date?->toDateString(),
            'first_response_at' => $this->first_response_at?->toISOString(),
            'closed_at' => $this->closed_at?->toISOString(),
            'close_reason' => $this->close_reason,
            'notes' => $this->when(
                $this->relationLoaded('notes'),
                fn () => LeadNoteResource::collection($this->notes),
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
