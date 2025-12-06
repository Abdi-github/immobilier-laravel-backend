<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PropertyImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            '_id' => (string) $this->id,
            'property_id' => (string) $this->property_id,
            'public_id' => $this->public_id,
            'version' => $this->version,
            'signature' => $this->signature,
            'url' => $this->url,
            'secure_url' => $this->secure_url,
            'thumbnail_url' => $this->thumbnail_url,
            'thumbnail_secure_url' => $this->thumbnail_secure_url,
            'width' => $this->width,
            'height' => $this->height,
            'format' => $this->format,
            'bytes' => $this->bytes,
            'resource_type' => $this->resource_type,
            'alt_text' => $this->alt_text,
            'caption' => $this->caption,
            'sort_order' => $this->sort_order,
            'is_primary' => $this->is_primary,
            'source' => $this->source?->value,
            'original_filename' => $this->original_filename,
            'external_url' => $this->external_url,
            'original_url' => $this->original_url,
            'migrated_at' => $this->migrated_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
