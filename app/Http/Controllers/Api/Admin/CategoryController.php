<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domain\Catalog\Enums\AmenityGroup;
use App\Domain\Catalog\Enums\CategorySection;
use App\Domain\Catalog\Models\Amenity;
use App\Domain\Catalog\Models\Category;
use App\Http\Controllers\Controller;
use App\Http\Resources\AmenityResource;
use App\Http\Resources\CategoryResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class CategoryController extends Controller
{
    use ApiResponse;

    public function store(Request $request): JsonResponse
    {
        $this->authorize('categories:create');

        $validated = $request->validate([
            'section' => 'required|string|in:' . implode(',', array_column(CategorySection::cases(), 'value')),
            'name' => 'required|array',
            'name.en' => 'required|string',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        // Auto-generate slug from English name
        $slug = Str::slug($validated['name']['en']);
        $baseSlug = $slug;
        $counter = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }
        $validated['slug'] = $slug;

        $category = Category::create($validated);

        return $this->createdResponse(new CategoryResource($category), __('categories.created'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorize('categories:create');

        $category = Category::find($id);
        if (! $category) {
            return $this->notFoundResponse(__('categories.not_found'));
        }

        $validated = $request->validate([
            'section' => 'sometimes|string|in:' . implode(',', array_column(CategorySection::cases(), 'value')),
            'name' => 'sometimes|array',
            'name.en' => 'sometimes|string',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        // Regenerate slug if name changed
        if (isset($validated['name']['en'])) {
            $slug = Str::slug($validated['name']['en']);
            $baseSlug = $slug;
            $counter = 1;
            while (Category::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }
            $validated['slug'] = $slug;
        }

        $category->update($validated);

        return $this->successResponse(new CategoryResource($category->fresh()), __('categories.updated'));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->authorize('categories:delete');

        $category = Category::find($id);
        if (! $category) {
            return $this->notFoundResponse(__('categories.not_found'));
        }

        $category->delete();

        return $this->successResponse(null, __('categories.deleted'));
    }
}
