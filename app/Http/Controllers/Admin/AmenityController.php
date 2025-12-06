<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Catalog\Enums\AmenityGroup;
use App\Domain\Catalog\Models\Amenity;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AmenityController extends Controller
{
    public function index(Request $request): Response
    {
        $amenities = Amenity::query()
            ->when($request->query('group'), fn ($q, $g) => $q->where('group', $g))
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Amenities/Index', [
            'amenities' => $amenities->map(fn (Amenity $a) => [
                'id' => $a->id,
                'name' => $a->getTranslations('name'),
                'group' => $a->group->value,
                'icon' => $a->icon,
                'sort_order' => $a->sort_order,
                'is_active' => $a->is_active,
            ]),
            'groups' => array_column(AmenityGroup::cases(), 'value'),
            'filters' => $request->only(['group']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|array',
            'name.en' => 'required|string',
            'group' => 'required|string|in:' . implode(',', array_column(AmenityGroup::cases(), 'value')),
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        Amenity::create($validated);

        return redirect()->back()->with('success', __('amenities.created'));
    }

    public function update(Request $request, Amenity $amenity): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|array',
            'name.en' => 'sometimes|string',
            'group' => 'sometimes|string|in:' . implode(',', array_column(AmenityGroup::cases(), 'value')),
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        $amenity->update($validated);

        return redirect()->back()->with('success', __('amenities.updated'));
    }

    public function destroy(Amenity $amenity): RedirectResponse
    {
        $amenity->delete();

        return redirect()->back()->with('success', __('amenities.deleted'));
    }
}
