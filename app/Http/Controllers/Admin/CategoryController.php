<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Catalog\Enums\CategorySection;
use App\Domain\Catalog\Models\Category;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $categories = Category::query()
            ->when($request->query('section'), fn ($q, $s) => $q->where('section', $s))
            ->withCount('properties')
            ->orderBy('section')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Categories/Index', [
            'categories' => $categories->map(fn (Category $c) => [
                'id' => $c->id,
                'section' => $c->section->value,
                'name' => $c->getTranslations('name'),
                'slug' => $c->slug,
                'icon' => $c->icon,
                'sort_order' => $c->sort_order,
                'is_active' => $c->is_active,
                'properties_count' => $c->properties_count ?? 0,
            ]),
            'sections' => array_column(CategorySection::cases(), 'value'),
            'filters' => $request->only(['section']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'section' => 'required|string|in:' . implode(',', array_column(CategorySection::cases(), 'value')),
            'name' => 'required|array',
            'name.en' => 'required|string',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        $slug = Str::slug($validated['name']['en']);
        $baseSlug = $slug;
        $counter = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }
        $validated['slug'] = $slug;

        Category::create($validated);

        return redirect()->back()->with('success', __('categories.created'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'section' => 'sometimes|string|in:' . implode(',', array_column(CategorySection::cases(), 'value')),
            'name' => 'sometimes|array',
            'name.en' => 'sometimes|string',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($validated['name']['en'])) {
            $slug = Str::slug($validated['name']['en']);
            $baseSlug = $slug;
            $counter = 1;
            while (Category::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }
            $validated['slug'] = $slug;
        }

        $category->update($validated);

        return redirect()->back()->with('success', __('categories.updated'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()->back()->with('success', __('categories.deleted'));
    }
}
