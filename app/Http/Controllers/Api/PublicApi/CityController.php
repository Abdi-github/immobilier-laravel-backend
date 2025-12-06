<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\PublicApi;

use App\Domain\Location\Services\LocationService;
use App\Http\Controllers\Controller;
use App\Http\Resources\CityResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CityController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly LocationService $locationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['page', 'limit', 'sort', 'order', 'is_active', 'canton_id', 'postal_code', 'search']);
        $cities = $this->locationService->getAllCities($filters);

        return $this->paginatedResponse(
            $cities->through(fn ($city) => new CityResource($city)),
            __('common.retrieved'),
        );
    }

    public function show(int $id): JsonResponse
    {
        $city = $this->locationService->getCityById($id);

        if (! $city) {
            return $this->notFoundResponse(__('locations.city_not_found'));
        }

        return $this->successResponse(
            new CityResource($city),
            __('common.retrieved'),
        );
    }

    public function popular(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 12);
        $minProperties = (int) $request->input('min_properties', 10);
        $cities = $this->locationService->getPopularCities($limit, $minProperties);

        return $this->successResponse(
            CityResource::collection($cities),
            __('common.retrieved'),
        );
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 1) {
            return $this->successResponse([], __('common.retrieved'));
        }

        $cities = $this->locationService->searchCities($query);

        return $this->successResponse(
            CityResource::collection($cities),
            __('common.retrieved'),
        );
    }

    public function byPostalCode(string $postalCode): JsonResponse
    {
        $cities = $this->locationService->getCitiesByPostalCode($postalCode);

        return $this->successResponse(
            CityResource::collection($cities),
            __('common.retrieved'),
        );
    }
}
