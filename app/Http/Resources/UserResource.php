<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            '_id' => (string) $this->id,
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
            'avatar_url' => $this->avatar_url,
            'user_type' => $this->user_type->value,
            'agency_id' => $this->agency_id ? (string) $this->agency_id : null,
            'agency' => $this->when($this->relationLoaded('agency') && $this->agency, fn () => [
                'id' => (string) $this->agency->id,
                'name' => $this->agency->name,
                'slug' => $this->agency->slug,
            ]),
            'preferred_language' => $this->preferred_language,
            'status' => $this->status->value,
            'email_verified' => $this->email_verified_at !== null,
            'roles' => $this->when(
                $this->relationLoaded('roles'),
                fn () => $this->roles->pluck('name')->toArray(),
            ),
            'permissions' => $this->when(
                $this->relationLoaded('roles'),
                fn () => $this->getAllPermissions()->pluck('name')->toArray(),
            ),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
