<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domain\Catalog\Enums\AmenityGroup;
use App\Domain\Catalog\Models\Amenity;
use App\Http\Controllers\Controller;
use App\Http\Resources\AmenityResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AmenityController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('amenities:read');

        $query = Amenity::query()
            ->when($request->query('group'), fn ($q, $g) => $q->where('group', $g))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN)))
            ->orderBy('group')
            ->orderBy('sort_order');

        $amenities = $query->get();

        return $this->successResponse(
            AmenityResource::collection($amenities),
            __('amenities.listed'),
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('amenities:create');

        $validated = $request->validate([
            'name' => 'required|array',
            'name.en' => 'required|string',
            'group' => 'required|string|in:' . implode(',', array_column(AmenityGroup::cases(), 'value')),
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        $amenity = Amenity::create($validated);

        return $this->createdResponse(new AmenityResource($amenity), __('amenities.created'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorize('amenities:create');

        $amenity = Amenity::find($id);
        if (! $amenity) {
            return $this->notFoundResponse(__('amenities.not_found'));
        }

        $validated = $request->validate([
            'name' => 'sometimes|array',
            'name.en' => 'sometimes|string',
            'group' => 'sometimes|string|in:' . implode(',', array_column(AmenityGroup::cases(), 'value')),
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        $amenity->update($validated);

        return $this->successResponse(new AmenityResource($amenity->fresh()), __('amenities.updated'));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->authorize('amenities:delete');

        $amenity = Amenity::find($id);
        if (! $amenity) {
            return $this->notFoundResponse(__('amenities.not_found'));
        }

        $amenity->delete();

        return $this->successResponse(null, __('amenities.deleted'));
    }
}
