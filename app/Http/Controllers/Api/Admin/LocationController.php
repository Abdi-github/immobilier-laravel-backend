<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domain\Location\Models\Canton;
use App\Domain\Location\Models\City;
use App\Http\Controllers\Controller;
use App\Http\Resources\CantonResource;
use App\Http\Resources\CityResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LocationController extends Controller
{
    use ApiResponse;

    // ── Cantons ──────────────────────────────────────────

    public function storeCanton(Request $request): JsonResponse
    {
        $this->authorize('locations:create');

        $validated = $request->validate([
            'code' => 'required|string|size:2|unique:cantons,code',
            'name' => 'required|array',
            'name.en' => 'required|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $validated['code'] = strtoupper($validated['code']);
        $canton = Canton::create($validated);

        return $this->createdResponse(new CantonResource($canton), __('locations.canton_created'));
    }

    public function updateCanton(Request $request, int $id): JsonResponse
    {
        $this->authorize('locations:update');

        $canton = Canton::find($id);
        if (! $canton) {
            return $this->notFoundResponse(__('locations.canton_not_found'));
        }

        $validated = $request->validate([
            'code' => "sometimes|string|size:2|unique:cantons,code,{$id}",
            'name' => 'sometimes|array',
            'name.en' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($validated['code'])) {
            $validated['code'] = strtoupper($validated['code']);
        }

        $canton->update($validated);

        return $this->successResponse(new CantonResource($canton->fresh()), __('locations.canton_updated'));
    }

    public function destroyCanton(int $id): JsonResponse
    {
        $this->authorize('locations:delete');

        $canton = Canton::find($id);
        if (! $canton) {
            return $this->notFoundResponse(__('locations.canton_not_found'));
        }

        $canton->delete();

        return $this->successResponse(null, __('locations.canton_deleted'));
    }

    // ── Cities ───────────────────────────────────────────

    public function storeCity(Request $request): JsonResponse
    {
        $this->authorize('locations:create');

        $validated = $request->validate([
            'canton_id' => 'required|integer|exists:cantons,id',
            'name' => 'required|array',
            'name.en' => 'required|string',
            'postal_code' => 'required|string|max:10',
            'image_url' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ]);

        $city = City::create($validated);
        $city->load('canton');

        return $this->createdResponse(new CityResource($city), __('locations.city_created'));
    }

    public function updateCity(Request $request, int $id): JsonResponse
    {
        $this->authorize('locations:update');

        $city = City::find($id);
        if (! $city) {
            return $this->notFoundResponse(__('locations.city_not_found'));
        }

        $validated = $request->validate([
            'canton_id' => 'sometimes|integer|exists:cantons,id',
            'name' => 'sometimes|array',
            'name.en' => 'sometimes|string',
            'postal_code' => 'sometimes|string|max:10',
            'image_url' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ]);

        $city->update($validated);
        $city->load('canton');

        return $this->successResponse(new CityResource($city->fresh('canton')), __('locations.city_updated'));
    }

    public function destroyCity(int $id): JsonResponse
    {
        $this->authorize('locations:delete');

        $city = City::find($id);
        if (! $city) {
            return $this->notFoundResponse(__('locations.city_not_found'));
        }

        $city->delete();

        return $this->successResponse(null, __('locations.city_deleted'));
    }
}
