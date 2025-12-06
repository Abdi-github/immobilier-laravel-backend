<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Repositories\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class EloquentCategoryRepository extends BaseRepository implements CategoryRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Category::class);
    }

    public function findBySlug(string $slug): ?Category
    {
        return Category::where('slug', $slug)->first();
    }

    public function findBySection(string $section): Collection
    {
        return Category::where('section', $section)
            ->active()
            ->ordered()
            ->get();
    }
}
