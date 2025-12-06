<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\PublicApi;

use App\Domain\Property\Services\PropertyService;
use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyImageResource;
use App\Http\Resources\PropertyResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PropertyController extends Controller
{
    use ApiResponse;

    private const FILTER_KEYS = [
        'page', 'limit', 'sort', 'order', 'search',
        'canton_id', 'city_id', 'postal_code', 'category_id',
        'transaction_type', 'agency_id',
        'price_min', 'price_max', 'rooms_min', 'rooms_max',
        'surface_min', 'surface_max', 'amenities',
    ];

    public function __construct(
        private readonly PropertyService $propertyService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(self::FILTER_KEYS);
        $properties = $this->propertyService->getPublishedProperties($filters);

        return $this->paginatedResponse(
            $properties->through(fn ($p) => new PropertyResource($p)),
            __('common.retrieved'),
        );
    }

    public function cursor(Request $request): JsonResponse
    {
        $filters = $request->only(self::FILTER_KEYS);
        $properties = $this->propertyService->getPublishedPropertiesWithCursor($filters);

        return $this->cursorPaginatedResponse(
            $properties->through(fn ($p) => new PropertyResource($p)),
            __('common.retrieved'),
        );
    }

    public function show(int $id): JsonResponse
    {
        $property = $this->propertyService->getPublishedPropertyById($id);

        if (! $property) {
            return $this->notFoundResponse(__('properties.not_found'));
        }

        return $this->successResponse(
            new PropertyResource($property),
            __('common.retrieved'),
        );
    }

    public function showByExternalId(string $externalId): JsonResponse
    {
        $property = $this->propertyService->getPropertyByExternalId($externalId);

        if (! $property || ! $property->isPublished()) {
            return $this->notFoundResponse(__('properties.not_found'));
        }

        return $this->successResponse(
            new PropertyResource($property),
            __('common.retrieved'),
        );
    }

    public function byCanton(int $cantonId, Request $request): JsonResponse
    {
        $filters = $request->only(self::FILTER_KEYS);
        $properties = $this->propertyService->getPropertiesByCanton($cantonId, $filters);

        return $this->paginatedResponse(
            $properties->through(fn ($p) => new PropertyResource($p)),
            __('common.retrieved'),
        );
    }

    public function byCity(int $cityId, Request $request): JsonResponse
    {
        $filters = $request->only(self::FILTER_KEYS);
        $properties = $this->propertyService->getPropertiesByCity($cityId, $filters);

        return $this->paginatedResponse(
            $properties->through(fn ($p) => new PropertyResource($p)),
            __('common.retrieved'),
        );
    }

    public function byAgency(int $agencyId, Request $request): JsonResponse
    {
        $filters = $request->only(self::FILTER_KEYS);
        $properties = $this->propertyService->getPropertiesByAgency($agencyId, $filters);

        return $this->paginatedResponse(
            $properties->through(fn ($p) => new PropertyResource($p)),
            __('common.retrieved'),
        );
    }

    public function byCategory(int $categoryId, Request $request): JsonResponse
    {
        $filters = $request->only(self::FILTER_KEYS);
        $properties = $this->propertyService->getPropertiesByCategory($categoryId, $filters);

        return $this->paginatedResponse(
            $properties->through(fn ($p) => new PropertyResource($p)),
            __('common.retrieved'),
        );
    }

    public function images(int $id): JsonResponse
    {
        $property = $this->propertyService->getPublishedPropertyById($id);

        if (! $property) {
            return $this->notFoundResponse(__('properties.not_found'));
        }

        $images = $this->propertyService->getPropertyImages($id);

        return $this->successResponse(
            PropertyImageResource::collection($images),
            __('common.retrieved'),
        );
    }
}
