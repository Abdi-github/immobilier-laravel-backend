<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();
        $translation = null;
        if ($this->relationLoaded('translations')) {
            $translation = $this->translations->firstWhere('language', $locale)
                ?? $this->translations->firstWhere('language', $this->source_language ?? 'en')
                ?? $this->translations->first();
        }

        return [
            '_id' => (string) $this->id,
            'external_id' => $this->external_id,
            'external_url' => $this->external_url,
            'source_language' => $this->source_language,
            'title' => $translation?->title,
            'description' => $translation?->description,
            'category_id' => (string) $this->category_id,
            'category' => $this->when(
                $this->relationLoaded('category'),
                fn () => new CategoryResource($this->category),
            ),
            'agency_id' => $this->agency_id ? (string) $this->agency_id : null,
            'agency' => $this->when(
                $this->relationLoaded('agency'),
                fn () => $this->agency ? new AgencyResource($this->agency) : null,
            ),
            'owner_id' => $this->owner_id ? (string) $this->owner_id : null,
            'transaction_type' => $this->transaction_type->value,
            'price' => (float) $this->price,
            'currency' => $this->currency,
            'additional_costs' => $this->additional_costs ? (float) $this->additional_costs : null,
            'rooms' => $this->rooms ? (float) $this->rooms : null,
            'surface' => $this->surface ? (float) $this->surface : null,
            'address' => $this->address,
            'city_id' => (string) $this->city_id,
            'city' => $this->when(
                $this->relationLoaded('city'),
                fn () => new CityResource($this->city),
            ),
            'canton_id' => (string) $this->canton_id,
            'canton' => $this->when(
                $this->relationLoaded('canton'),
                fn () => new CantonResource($this->canton),
            ),
            'postal_code' => $this->postal_code,
            'proximity' => $this->proximity,
            'status' => $this->status->value,
            'reviewed_by' => $this->reviewed_by ? (string) $this->reviewed_by : null,
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'rejection_reason' => $this->rejection_reason,
            'published_at' => $this->published_at?->toISOString(),
            'images' => $this->when(
                $this->relationLoaded('images'),
                fn () => PropertyImageResource::collection($this->images),
            ),
            'primary_image' => $this->when(
                $this->relationLoaded('primaryImage'),
                fn () => $this->primaryImage ? new PropertyImageResource($this->primaryImage) : null,
            ),
            'amenities' => $this->when(
                $this->relationLoaded('amenities'),
                fn () => AmenityResource::collection($this->amenities),
            ),
            'translations' => $this->when(
                $this->relationLoaded('translations') && $request->has('include_translations'),
                fn () => PropertyTranslationResource::collection($this->translations),
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
