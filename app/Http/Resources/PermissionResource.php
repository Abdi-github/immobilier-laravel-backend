<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PermissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            '_id' => (string) $this->id,
            'name' => $this->name,
            'display_name' => is_array($this->display_name)
                ? ($this->display_name[$locale] ?? $this->display_name['en'] ?? $this->name)
                : $this->display_name,
            'display_name_translations' => $this->display_name,
            'description' => is_array($this->description)
                ? ($this->description[$locale] ?? $this->description['en'] ?? '')
                : $this->description,
            'description_translations' => $this->description,
            'resource' => $this->resource,
            'action' => $this->action,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
