<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\PublicApi;

use App\Domain\Search\Services\SearchService;
use App\Http\Controllers\Controller;
use App\Http\Resources\CantonResource;
use App\Http\Resources\CityResource;
use App\Http\Resources\PropertyResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SearchController extends Controller
{
    use ApiResponse;

    private const FILTER_KEYS = [
        'q', 'page', 'limit', 'sort', 'order',
        'canton_id', 'city_id', 'postal_code', 'category_id',
        'transaction_type', 'agency_id',
        'price_min', 'price_max', 'rooms_min', 'rooms_max',
        'surface_min', 'surface_max', 'amenities',
    ];

    public function __construct(
        private readonly SearchService $searchService,
    ) {}

    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        // \Log::debug('Unified search', ['q' => substr($query, 0, 30), 'len' => strlen($query)]);

        if (strlen($query) < 1) {
            // \Log::debug('Query too short');
            return $this->successResponse([
                'properties' => [],
                'cantons' => [],
                'cities' => [],
            ], __('common.retrieved'));
        }

        $limit = (int) $request->input('limit', 5);
        // \Log::debug('Executing unified search', compact('limit'));
        
        $results = $this->searchService->unifiedSearch($query, ['limit' => $limit]);
        // \Log::debug('✓ Results found', ['props' => count($results['properties']), 'cantons' => count($results['cantons']), 'cities' => count($results['cities'])]);

        return $this->successResponse([
            'properties' => PropertyResource::collection($results['properties']),
            'cantons' => CantonResource::collection($results['cantons']),
            'cities' => CityResource::collection($results['cities']),
        ], __('common.retrieved'));
    }

    public function properties(Request $request): JsonResponse
    {
        // \Log::debug('Property search request', ['filters' => array_keys($request->query())]);
        
        $filters = $request->only(self::FILTER_KEYS);
        $properties = $this->searchService->searchProperties($filters);
        // \Log::debug('Search executed', ['count' => $properties->total()]);

        return $this->paginatedResponse(
            $properties->through(fn ($p) => new PropertyResource($p)),
            __('common.retrieved'),
        );
    }

    public function propertiesCursor(Request $request): JsonResponse
    {
        // \Log::debug('Cursor paginated search');
        
        $filters = $request->only(self::FILTER_KEYS);
        $properties = $this->searchService->searchPropertiesWithCursor($filters);
        // \Log::debug('✓ Cursor results prepared');

        return $this->cursorPaginatedResponse(
            $properties->through(fn ($p) => new PropertyResource($p)),
            __('common.retrieved'),
        );
    }

    public function locations(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 1) {
            return $this->successResponse(['cantons' => [], 'cities' => []], __('common.retrieved'));
        }

        $options = [
            'limit' => (int) $request->input('limit', 10),
            'include_cantons' => filter_var($request->input('include_cantons', true), FILTER_VALIDATE_BOOLEAN),
            'include_cities' => filter_var($request->input('include_cities', true), FILTER_VALIDATE_BOOLEAN),
        ];

        $results = $this->searchService->searchLocations($query, $options);

        return $this->successResponse([
            'cantons' => isset($results['cantons']) ? CantonResource::collection($results['cantons']) : [],
            'cities' => isset($results['cities']) ? CityResource::collection($results['cities']) : [],
        ], __('common.retrieved'));
    }

    public function suggestions(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return $this->successResponse([], __('common.retrieved'));
        }

        $limit = (int) $request->input('limit', 5);
        $suggestions = $this->searchService->getSuggestions($query, $limit);

        return $this->successResponse($suggestions, __('common.retrieved'));
    }

    public function facets(Request $request): JsonResponse
    {
        $filters = $request->only([
            'canton_id', 'city_id', 'category_id', 'transaction_type',
            'price_min', 'price_max', 'fields',
        ]);

        $facets = $this->searchService->getFacets($filters);

        return $this->successResponse($facets, __('common.retrieved'));
    }
}
