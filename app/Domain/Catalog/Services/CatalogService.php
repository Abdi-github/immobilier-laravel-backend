<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\Amenity;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Repositories\AmenityRepositoryInterface;
use App\Domain\Catalog\Repositories\CategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class CatalogService
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly AmenityRepositoryInterface $amenityRepository,
    ) {}

    // ── Categories ──

    public function getAllCategories(array $filters = []): LengthAwarePaginator
    {
        return Category::query()
            ->when(
                isset($filters['is_active']),
                fn ($q) => $q->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN)),
            )
            ->when($filters['section'] ?? null, fn ($q, $s) => $q->where('section', $s))
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->whereRaw("name::text ILIKE ?", ["%{$s}%"]))
            ->ordered()
            ->paginate($filters['limit'] ?? 50, ['*'], 'page', $filters['page'] ?? 1);
    }

    public function getCategoryById(int $id): ?Category
    {
        return $this->categoryRepository->findById($id);
    }

    public function getCategoryBySlug(string $slug): ?Category
    {
        return $this->categoryRepository->findBySlug($slug);
    }

    public function getCategoriesBySection(string $section): Collection
    {
        return $this->categoryRepository->findBySection($section);
    }

    // ── Amenities ──

    public function getAllAmenities(array $filters = []): LengthAwarePaginator
    {
        return Amenity::query()
            ->when(
                isset($filters['is_active']),
                fn ($q) => $q->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN)),
            )
            ->when($filters['group'] ?? null, fn ($q, $g) => $q->where('group', $g))
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->whereRaw("name::text ILIKE ?", ["%{$s}%"]))
            ->ordered()
            ->paginate($filters['limit'] ?? 100, ['*'], 'page', $filters['page'] ?? 1);
    }

    public function getAmenityById(int $id): ?Amenity
    {
        return $this->amenityRepository->findById($id);
    }

    public function getAmenitiesByGroup(string $group): Collection
    {
        return $this->amenityRepository->findByGroup($group);
    }
}
