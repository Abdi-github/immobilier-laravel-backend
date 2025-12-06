<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Agent;

use App\Domain\Property\Services\PropertyService;
use App\Domain\User\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyImageResource;
use App\Http\Resources\PropertyResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PropertyController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly PropertyService $propertyService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('properties:read');

        $filters = $request->only([
            'page', 'limit', 'sort', 'order', 'search',
            'status', 'canton_id', 'city_id', 'postal_code',
            'category_id', 'transaction_type',
            'price_min', 'price_max', 'rooms_min', 'rooms_max',
            'surface_min', 'surface_max', 'amenities',
        ]);

        [$agencyId, $ownerId] = $this->resolveOwnershipScope($request);
        if ($agencyId) {
            $filters['agency_id'] = $agencyId;
        }
        if ($ownerId) {
            $filters['owner_id'] = $ownerId;
        }

        $paginator = $this->propertyService->getAllProperties($filters);
        $paginator->through(fn ($p) => new PropertyResource($p));

        return $this->paginatedResponse($paginator, __('properties.list_retrieved'));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->authorize('properties:read');

        $property = $this->propertyService->getPropertyById($id);
        if (! $property) {
            return $this->notFoundResponse(__('properties.not_found'));
        }

        $this->ensureOwnership($request, $property);

        return $this->successResponse(new PropertyResource($property), __('properties.retrieved'));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('properties:create');

        $validated = $request->validate([
            'external_id' => 'nullable|string|max:100|unique:properties,external_id',
            'external_url' => 'nullable|url|max:1000',
            'source_language' => 'nullable|string|in:en,fr,de,it',
            'category_id' => 'required|integer|exists:categories,id',
            'transaction_type' => 'required|string|in:rent,buy',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'additional_costs' => 'nullable|numeric|min:0',
            'rooms' => 'nullable|numeric|min:0',
            'surface' => 'nullable|numeric|min:0',
            'address' => 'required|string|max:500',
            'city_id' => 'required|integer|exists:cities,id',
            'canton_id' => 'required|integer|exists:cantons,id',
            'postal_code' => 'nullable|string|max:10',
            'proximity' => 'nullable|array',
            'amenities' => 'nullable|array',
            'amenities.*' => 'integer|exists:amenities,id',
        ]);

        $user = $request->user();
        $validated['owner_id'] = $user->id;

        if ($user->agency_id) {
            $validated['agency_id'] = $user->agency_id;
        }

        $property = $this->propertyService->createProperty($validated);

        return $this->createdResponse(new PropertyResource($property), __('properties.created'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorize('properties:update');

        $property = $this->propertyService->getPropertyById($id);
        if (! $property) {
            return $this->notFoundResponse(__('properties.not_found'));
        }

        $this->ensureOwnership($request, $property);

        $validated = $request->validate([
            'external_url' => 'nullable|url|max:1000',
            'source_language' => 'nullable|string|in:en,fr,de,it',
            'category_id' => 'nullable|integer|exists:categories,id',
            'transaction_type' => 'nullable|string|in:rent,buy',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'additional_costs' => 'nullable|numeric|min:0',
            'rooms' => 'nullable|numeric|min:0',
            'surface' => 'nullable|numeric|min:0',
            'address' => 'nullable|string|max:500',
            'city_id' => 'nullable|integer|exists:cities,id',
            'canton_id' => 'nullable|integer|exists:cantons,id',
            'postal_code' => 'nullable|string|max:10',
            'proximity' => 'nullable|array',
            'amenities' => 'nullable|array',
            'amenities.*' => 'integer|exists:amenities,id',
        ]);

        $property = $this->propertyService->updateProperty($id, $validated);

        return $this->successResponse(new PropertyResource($property), __('properties.updated'));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->authorize('properties:delete');

        $property = $this->propertyService->getPropertyById($id);
        if (! $property) {
            return $this->notFoundResponse(__('properties.not_found'));
        }

        $this->ensureOwnership($request, $property);

        $this->propertyService->deleteProperty($id);

        return $this->successResponse(null, __('properties.deleted'));
    }

    public function submit(Request $request, int $id): JsonResponse
    {
        $this->authorize('properties:update');

        $property = $this->propertyService->getPropertyById($id);
        if (! $property) {
            return $this->notFoundResponse(__('properties.not_found'));
        }

        $this->ensureOwnership($request, $property);

        $property = $this->propertyService->submitForApproval($id);

        return $this->successResponse(new PropertyResource($property), __('properties.submitted'));
    }

    public function images(Request $request, int $id): JsonResponse
    {
        $this->authorize('properties:read');

        $property = $this->propertyService->getPropertyById($id);
        if (! $property) {
            return $this->notFoundResponse(__('properties.not_found'));
        }

        $this->ensureOwnership($request, $property);

        $images = $this->propertyService->getPropertyImages($id);

        return $this->successResponse(
            PropertyImageResource::collection($images),
            __('properties.images_retrieved'),
        );
    }

    public function uploadImage(Request $request, int $id): JsonResponse
    {
        $this->authorize('properties:update');

        $property = $this->propertyService->getPropertyById($id);
        if (! $property) {
            return $this->notFoundResponse(__('properties.not_found'));
        }

        $this->ensureOwnership($request, $property);

        $validated = $request->validate([
            'url' => 'required|string|max:1000',
            'secure_url' => 'nullable|string|max:1000',
            'thumbnail_url' => 'nullable|string|max:1000',
            'thumbnail_secure_url' => 'nullable|string|max:1000',
            'public_id' => 'nullable|string|max:255',
            'alt_text' => 'nullable|string|max:500',
            'caption' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'is_primary' => 'nullable|boolean',
            'source' => 'nullable|string|max:20',
            'original_filename' => 'nullable|string|max:500',
        ]);

        $image = $this->propertyService->addImage($id, $validated);

        return $this->createdResponse(new PropertyImageResource($image), __('properties.image_uploaded'));
    }

    public function deleteImage(Request $request, int $id, int $imageId): JsonResponse
    {
        $this->authorize('properties:update');

        $property = $this->propertyService->getPropertyById($id);
        if (! $property) {
            return $this->notFoundResponse(__('properties.not_found'));
        }

        $this->ensureOwnership($request, $property);

        $this->propertyService->deleteImage($id, $imageId);

        return $this->successResponse(null, __('properties.image_deleted'));
    }

    /**
     * @return array{0: ?int, 1: ?int} [$agencyId, $ownerId]
     */
    private function resolveOwnershipScope(Request $request): array
    {
        $user = $request->user();

        return match ($user->user_type) {
            UserType::AGENT, UserType::AGENCY_ADMIN => [$user->agency_id, null],
            UserType::OWNER => [null, $user->id],
            default => [null, null],
        };
    }

    private function ensureOwnership(Request $request, $property): void
    {
        $user = $request->user();

        // Admins bypass ownership check
        if ($user->user_type->isAdmin()) {
            return;
        }

        // Agency members can access agency properties
        if ($user->agency_id && $property->agency_id === $user->agency_id) {
            return;
        }

        // Owner can access their own properties
        if ($property->owner_id === $user->id) {
            return;
        }

        abort(403, __('properties.ownership_required'));
    }
}
