<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class FavoriteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            '_id' => (string) $this->id,
            'user_id' => (string) $this->user_id,
            'property_id' => (string) $this->property_id,
            'property' => $this->when(
                $this->relationLoaded('property'),
                fn () => $this->property ? new PropertyResource($this->property) : null,
            ),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
