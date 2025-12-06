<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PropertyTranslationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            '_id' => (string) $this->id,
            'property_id' => (string) $this->property_id,
            'language' => $this->language,
            'title' => $this->title,
            'description' => $this->description,
            'source' => $this->source?->value,
            'quality_score' => $this->quality_score,
            'approval_status' => $this->approval_status?->value,
            'approved_by' => $this->approved_by ? (string) $this->approved_by : null,
            'approved_at' => $this->approved_at?->toISOString(),
            'rejection_reason' => $this->rejection_reason,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
