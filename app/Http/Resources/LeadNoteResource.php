<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class LeadNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            '_id' => (string) $this->id,
            'lead_id' => (string) $this->lead_id,
            'content' => $this->content,
            'is_internal' => $this->is_internal,
            'created_by' => (string) $this->created_by,
            'created_by_user' => $this->when(
                $this->relationLoaded('createdBy'),
                fn () => $this->createdBy ? new UserResource($this->createdBy) : null,
            ),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
