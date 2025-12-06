<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CantonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            '_id' => (string) $this->id,
            'code' => $this->code,
            'name' => $this->getTranslation('name', $locale),
            'name_translations' => $this->getTranslations('name'),
            'is_active' => $this->is_active,
            'cities_count' => $this->when($this->cities_count !== null, $this->cities_count),
            'properties_count' => $this->when($this->properties_count !== null, $this->properties_count),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
