<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            '_id' => (string) $this->id,
            'section' => $this->section->value,
            'name' => $this->getTranslation('name', $locale),
            'name_translations' => $this->getTranslations('name'),
            'slug' => $this->slug,
            'icon' => $this->icon,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'properties_count' => $this->when($this->properties_count !== null, $this->properties_count),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
