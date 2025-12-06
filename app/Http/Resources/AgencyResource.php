<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AgencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            '_id' => (string) $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->getTranslation('description', $locale),
            'description_translations' => $this->getTranslations('description'),
            'logo_url' => $this->logo_url,
            'website' => $this->website,
            'email' => $this->email,
            'phone' => $this->phone,
            'contact_person' => $this->contact_person,
            'address' => $this->address,
            'city_id' => (string) $this->city_id,
            'canton_id' => (string) $this->canton_id,
            'city' => $this->when(
                $this->relationLoaded('city'),
                fn () => new CityResource($this->city),
            ),
            'canton' => $this->when(
                $this->relationLoaded('canton'),
                fn () => new CantonResource($this->canton),
            ),
            'postal_code' => $this->postal_code,
            'status' => $this->status,
            'is_verified' => $this->is_verified,
            'verification_date' => $this->verification_date?->toISOString(),
            'total_properties' => $this->total_properties,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
