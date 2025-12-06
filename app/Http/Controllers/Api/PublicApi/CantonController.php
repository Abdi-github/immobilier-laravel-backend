<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\PublicApi;

use App\Domain\Location\Services\LocationService;
use App\Http\Controllers\Controller;
use App\Http\Resources\CantonResource;
use App\Http\Resources\CityResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CantonController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly LocationService $locationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['page', 'limit', 'sort', 'order', 'is_active', 'code', 'search']);
        $cantons = $this->locationService->getAllCantons($filters);

        return $this->paginatedResponse(
            $cantons->through(fn ($canton) => new CantonResource($canton)),
            __('common.retrieved'),
        );
    }

    public function show(int $id): JsonResponse
    {
        $canton = $this->locationService->getCantonById($id);

        if (! $canton) {
            return $this->notFoundResponse(__('locations.canton_not_found'));
        }

        return $this->successResponse(
            new CantonResource($canton),
            __('common.retrieved'),
        );
    }

    public function showByCode(string $code): JsonResponse
    {
        $canton = $this->locationService->getCantonByCode($code);

        if (! $canton) {
            return $this->notFoundResponse(__('locations.canton_not_found'));
        }

        return $this->successResponse(
            new CantonResource($canton),
            __('common.retrieved'),
        );
    }

    public function cities(int $id, Request $request): JsonResponse
    {
        $canton = $this->locationService->getCantonById($id);

        if (! $canton) {
            return $this->notFoundResponse(__('locations.canton_not_found'));
        }

        $filters = $request->only(['page', 'limit', 'sort', 'order', 'is_active', 'postal_code', 'search']);
        $cities = $this->locationService->getCantonCities($id, $filters);

        return $this->paginatedResponse(
            $cities->through(fn ($city) => new CityResource($city)),
            __('common.retrieved'),
        );
    }
}
