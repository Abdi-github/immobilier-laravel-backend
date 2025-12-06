<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\PublicApi;

use App\Domain\Agency\Services\AgencyService;
use App\Http\Controllers\Controller;
use App\Http\Resources\AgencyResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AgencyController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AgencyService $agencyService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'page', 'limit', 'sort', 'order', 'search',
            'canton_id', 'city_id', 'status', 'is_verified', 'include_inactive',
        ]);
        $agencies = $this->agencyService->getAllAgencies($filters);

        return $this->paginatedResponse(
            $agencies->through(fn ($a) => new AgencyResource($a)),
            __('common.retrieved'),
        );
    }

    public function show(int $id): JsonResponse
    {
        $agency = $this->agencyService->getAgencyById($id);

        if (! $agency) {
            return $this->notFoundResponse(__('agencies.not_found'));
        }

        return $this->successResponse(
            new AgencyResource($agency),
            __('common.retrieved'),
        );
    }

    public function showBySlug(string $slug): JsonResponse
    {
        $agency = $this->agencyService->getAgencyBySlug($slug);

        if (! $agency) {
            return $this->notFoundResponse(__('agencies.not_found'));
        }

        return $this->successResponse(
            new AgencyResource($agency),
            __('common.retrieved'),
        );
    }

    public function byCanton(int $cantonId, Request $request): JsonResponse
    {
        $filters = $request->only(['page', 'limit', 'sort', 'order']);
        $agencies = $this->agencyService->getAgenciesByCanton($cantonId, $filters);

        return $this->paginatedResponse(
            $agencies->through(fn ($a) => new AgencyResource($a)),
            __('common.retrieved'),
        );
    }

    public function byCity(int $cityId, Request $request): JsonResponse
    {
        $filters = $request->only(['page', 'limit', 'sort', 'order']);
        $agencies = $this->agencyService->getAgenciesByCity($cityId, $filters);

        return $this->paginatedResponse(
            $agencies->through(fn ($a) => new AgencyResource($a)),
            __('common.retrieved'),
        );
    }
}
