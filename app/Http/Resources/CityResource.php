<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            '_id' => (string) $this->id,
            'canton_id' => (string) $this->canton_id,
            'canton' => $this->when(
                $this->relationLoaded('canton'),
                fn () => new CantonResource($this->canton),
            ),
            'name' => $this->getTranslation('name', $locale),
            'name_translations' => $this->getTranslations('name'),
            'postal_code' => $this->postal_code,
            'image_url' => $this->image_url,
            'is_active' => $this->is_active,
            'properties_count' => $this->when($this->properties_count !== null, $this->properties_count),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
