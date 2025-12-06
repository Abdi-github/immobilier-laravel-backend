<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\PublicApi;

use App\Domain\Catalog\Services\CatalogService;
use App\Http\Controllers\Controller;
use App\Http\Resources\AmenityResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AmenityController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CatalogService $catalogService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['page', 'limit', 'sort', 'is_active', 'group', 'search']);
        $amenities = $this->catalogService->getAllAmenities($filters);

        return $this->paginatedResponse(
            $amenities->through(fn ($a) => new AmenityResource($a)),
            __('common.retrieved'),
        );
    }

    public function show(int $id): JsonResponse
    {
        $amenity = $this->catalogService->getAmenityById($id);

        if (! $amenity) {
            return $this->notFoundResponse(__('amenities.not_found'));
        }

        return $this->successResponse(
            new AmenityResource($amenity),
            __('common.retrieved'),
        );
    }

    public function byGroup(string $group): JsonResponse
    {
        $amenities = $this->catalogService->getAmenitiesByGroup($group);

        return $this->successResponse(
            AmenityResource::collection($amenities),
            __('common.retrieved'),
        );
    }
}
