<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domain\Property\Enums\PropertyStatus;
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

    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('properties:read');
        // \Log::debug('Property stats request');

        [$agencyId, $ownerId] = $this->resolveScope($request);
        // \Log::debug('Scope resolved', compact('agencyId', 'ownerId'));

        return $this->successResponse(
            $this->propertyService->getStatistics($agencyId, $ownerId),
            __('properties.statistics_retrieved'),
        );
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('properties:read');
        // \Log::debug('Listing properties', ['filters' => array_keys($request->query())]);

        $filters = $request->only([
            'page', 'limit', 'sort', 'order', 'search',
            'status', 'canton_id', 'city_id', 'postal_code',
            'category_id', 'transaction_type', 'agency_id', 'owner_id',
            'price_min', 'price_max', 'rooms_min', 'rooms_max',
            'surface_min', 'surface_max', 'amenities',
        ]);

        [$agencyId, $ownerId] = $this->resolveScope($request);
        if ($agencyId) {
            $filters['agency_id'] = $agencyId;
            // \Log::debug('Filtering by agency:', ['id' => $agencyId]);
        }
        if ($ownerId) {
            $filters['owner_id'] = $ownerId;
        }

        $paginator = $this->propertyService->getAllProperties($filters);
        // \Log::debug('✓ Query executed', ['count' => $paginator->count(), 'total' => $paginator->total()]);
        
        $paginator->through(fn ($p) => new PropertyResource($p));

        return $this->paginatedResponse($paginator, __('properties.list_retrieved'));
    }

    public function show(int $id): JsonResponse
    {
        $this->authorize('properties:read');
        // \Log::debug('Fetching property detail:', compact('id'));

        $property = $this->propertyService->getPropertyById($id);
        if (! $property) {
            // \Log::warning('Property not found', compact('id'));
            return $this->notFoundResponse(__('properties.not_found'));
        }
        // \Log::debug('✓ Property found');

        return $this->successResponse(new PropertyResource($property), __('properties.retrieved'));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('properties:create');
        // \Log::info('Creating new property', ['user' => auth()->id()]);

        $validated = $request->validate([
            'external_id' => 'nullable|string|max:100|unique:properties,external_id',
            'external_url' => 'nullable|url|max:1000',
            'source_language' => 'nullable|string|in:en,fr,de,it',
            'category_id' => 'required|integer|exists:categories,id',
            'agency_id' => 'nullable|integer|exists:agencies,id',
            'owner_id' => 'nullable|integer|exists:users,id',
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
            'status' => 'nullable|string|in:DRAFT,PENDING_APPROVAL',
        ]);

        $user = $request->user();

        // Auto-assign agency for agents
        if (in_array($user->user_type, [UserType::AGENT, UserType::AGENCY_ADMIN]) && empty($validated['agency_id'])) {
            $validated['agency_id'] = $user->agency_id;
        }

        // Auto-assign owner
        if (empty($validated['owner_id'])) {
            $validated['owner_id'] = $user->id;
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

        $validated = $request->validate([
            'external_url' => 'nullable|url|max:1000',
            'source_language' => 'nullable|string|in:en,fr,de,it',
            'category_id' => 'nullable|integer|exists:categories,id',
            'agency_id' => 'nullable|integer|exists:agencies,id',
            'owner_id' => 'nullable|integer|exists:users,id',
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

    public function destroy(int $id): JsonResponse
    {
        $this->authorize('properties:delete');

        $property = $this->propertyService->getPropertyById($id);
        if (! $property) {
            return $this->notFoundResponse(__('properties.not_found'));
        }

        $this->propertyService->deleteProperty($id);

        return $this->successResponse(null, __('properties.deleted'));
    }

    // ── Status Workflow ──

    public function submit(int $id): JsonResponse
    {
        $this->authorize('properties:update');

        $property = $this->propertyService->submitForApproval($id);

        return $this->successResponse(new PropertyResource($property), __('properties.submitted'));
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $this->authorize('properties:approve');

        $reviewerId = $request->input('reviewed_by', $request->user()->id);

        $property = $this->propertyService->approve($id, (int) $reviewerId);

        return $this->successResponse(new PropertyResource($property), __('properties.approved'));
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $this->authorize('properties:reject');

        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $reviewerId = $request->input('reviewed_by', $request->user()->id);

        $property = $this->propertyService->reject($id, (int) $reviewerId, $request->input('rejection_reason'));

        return $this->successResponse(new PropertyResource($property), __('properties.rejected'));
    }

    public function publish(int $id): JsonResponse
    {
        $this->authorize('properties:publish');

        $property = $this->propertyService->publish($id);

        return $this->successResponse(new PropertyResource($property), __('properties.published'));
    }

    public function archive(int $id): JsonResponse
    {
        $this->authorize('properties:archive');

        $property = $this->propertyService->archive($id);

        return $this->successResponse(new PropertyResource($property), __('properties.archived'));
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $this->authorize('properties:manage');

        $request->validate([
            'status' => 'required|string|in:DRAFT,PENDING_APPROVAL,APPROVED,REJECTED,PUBLISHED,ARCHIVED',
            'reviewed_by' => 'nullable|integer|exists:users,id',
            'rejection_reason' => 'required_if:status,REJECTED|nullable|string|max:1000',
        ]);

        $newStatus = PropertyStatus::from($request->input('status'));
        $reviewerId = $request->input('reviewed_by', $request->user()->id);

        $property = $this->propertyService->updateStatus(
            $id,
            $newStatus,
            (int) $reviewerId,
            $request->input('rejection_reason'),
        );

        return $this->successResponse(new PropertyResource($property), __('properties.status_updated'));
    }

    // ── Image Management ──

    public function images(int $id): JsonResponse
    {
        $this->authorize('properties:read');

        $property = $this->propertyService->getPropertyById($id);
        if (! $property) {
            return $this->notFoundResponse(__('properties.not_found'));
        }

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

        $validated = $request->validate([
            'url' => 'required|string|max:1000',
            'secure_url' => 'nullable|string|max:1000',
            'thumbnail_url' => 'nullable|string|max:1000',
            'thumbnail_secure_url' => 'nullable|string|max:1000',
            'public_id' => 'nullable|string|max:255',
            'version' => 'nullable|integer',
            'signature' => 'nullable|string|max:255',
            'width' => 'nullable|integer',
            'height' => 'nullable|integer',
            'format' => 'nullable|string|max:20',
            'bytes' => 'nullable|integer',
            'resource_type' => 'nullable|string|max:20',
            'alt_text' => 'nullable|string|max:500',
            'caption' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'is_primary' => 'nullable|boolean',
            'source' => 'nullable|string|max:20',
            'original_filename' => 'nullable|string|max:500',
            'external_url' => 'nullable|string|max:1000',
            'original_url' => 'nullable|string|max:1000',
        ]);

        $image = $this->propertyService->addImage($id, $validated);

        return $this->createdResponse(new PropertyImageResource($image), __('properties.image_uploaded'));
    }

    public function uploadImages(Request $request, int $id): JsonResponse
    {
        $this->authorize('properties:update');

        $property = $this->propertyService->getPropertyById($id);
        if (! $property) {
            return $this->notFoundResponse(__('properties.not_found'));
        }

        $request->validate([
            'images' => 'required|array|min:1|max:10',
            'images.*.url' => 'required|string|max:1000',
            'images.*.secure_url' => 'nullable|string|max:1000',
            'images.*.thumbnail_url' => 'nullable|string|max:1000',
            'images.*.thumbnail_secure_url' => 'nullable|string|max:1000',
            'images.*.public_id' => 'nullable|string|max:255',
            'images.*.alt_text' => 'nullable|string|max:500',
            'images.*.caption' => 'nullable|string|max:500',
            'images.*.sort_order' => 'nullable|integer|min:0',
            'images.*.is_primary' => 'nullable|boolean',
            'images.*.source' => 'nullable|string|max:20',
            'images.*.original_filename' => 'nullable|string|max:500',
        ]);

        $successful = [];
        $failed = [];

        foreach ($request->input('images') as $imageData) {
            try {
                $image = $this->propertyService->addImage($id, $imageData);
                $successful[] = new PropertyImageResource($image);
            } catch (\Throwable $e) {
                $failed[] = [
                    'filename' => $imageData['original_filename'] ?? $imageData['url'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $this->successResponse([
            'successful' => $successful,
            'failed' => $failed,
        ], __('properties.images_uploaded'));
    }

    public function reorderImages(Request $request, int $id): JsonResponse
    {
        $this->authorize('properties:update');

        $request->validate([
            'orders' => 'required|array',
            'orders.*' => 'integer',
        ]);

        $this->propertyService->reorderImages($id, $request->input('orders'));

        $images = $this->propertyService->getPropertyImages($id);

        return $this->successResponse(
            PropertyImageResource::collection($images),
            __('properties.images_reordered'),
        );
    }

    public function setPrimaryImage(int $id, int $imageId): JsonResponse
    {
        $this->authorize('properties:update');

        $this->propertyService->setPrimaryImage($id, $imageId);

        $images = $this->propertyService->getPropertyImages($id);

        return $this->successResponse(
            PropertyImageResource::collection($images),
            __('properties.primary_image_set'),
        );
    }

    public function deleteImage(int $id, int $imageId): JsonResponse
    {
        $this->authorize('properties:delete');

        $this->propertyService->deleteImage($id, $imageId);

        return $this->successResponse(null, __('properties.image_deleted'));
    }

    // ── Private helpers ──

    /**
     * Resolve scope based on user type. Admins see all, agents/owners see own.
     *
     * @return array{0: ?int, 1: ?int} [$agencyId, $ownerId]
     */
    private function resolveScope(Request $request): array
    {
        $user = $request->user();

        return match ($user->user_type) {
            UserType::AGENT, UserType::AGENCY_ADMIN => [$user->agency_id, null],
            UserType::OWNER => [null, $user->id],
            default => [null, null], // super_admin, platform_admin — no scope
        };
    }
}
