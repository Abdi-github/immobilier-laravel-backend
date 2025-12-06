<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AlertResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            '_id' => (string) $this->id,
            'user_id' => (string) $this->user_id,
            'name' => $this->name,
            'criteria' => $this->criteria,
            'frequency' => $this->frequency,
            'is_active' => $this->is_active,
            'last_sent_at' => $this->last_sent_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
