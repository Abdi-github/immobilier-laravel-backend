<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\PublicApi;

use App\Domain\Catalog\Services\CatalogService;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CategoryController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CatalogService $catalogService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['page', 'limit', 'sort', 'is_active', 'section', 'search']);
        $categories = $this->catalogService->getAllCategories($filters);

        return $this->paginatedResponse(
            $categories->through(fn ($cat) => new CategoryResource($cat)),
            __('common.retrieved'),
        );
    }

    public function show(int $id): JsonResponse
    {
        $category = $this->catalogService->getCategoryById($id);

        if (! $category) {
            return $this->notFoundResponse(__('categories.not_found'));
        }

        return $this->successResponse(
            new CategoryResource($category),
            __('common.retrieved'),
        );
    }

    public function showBySlug(string $slug): JsonResponse
    {
        $category = $this->catalogService->getCategoryBySlug($slug);

        if (! $category) {
            return $this->notFoundResponse(__('categories.not_found'));
        }

        return $this->successResponse(
            new CategoryResource($category),
            __('common.retrieved'),
        );
    }

    public function bySection(string $section): JsonResponse
    {
        $categories = $this->catalogService->getCategoriesBySection($section);

        return $this->successResponse(
            CategoryResource::collection($categories),
            __('common.retrieved'),
        );
    }
}
