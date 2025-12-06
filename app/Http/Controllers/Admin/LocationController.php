<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Location\Models\Canton;
use App\Domain\Location\Models\City;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class LocationController extends Controller
{
    public function index(Request $request): Response
    {
        $cantons = Canton::query()
            ->withCount('cities')
            ->orderBy('code')
            ->get()
            ->map(fn (Canton $c) => [
                'id' => $c->id,
                'code' => $c->code,
                'name' => $c->getTranslations('name'),
                'is_active' => $c->is_active,
                'cities_count' => $c->cities_count ?? 0,
            ]);

        $citiesQuery = City::query()
            ->with('canton')
            ->when($request->query('canton_id'), fn ($q, $id) => $q->where('canton_id', $id))
            ->when($request->query('city_search'), fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('postal_code', 'ILIKE', "%{$s}%")
                    ->orWhereRaw("name::text ILIKE ?", ["%{$s}%"]);
            }))
            ->orderBy('postal_code');

        $cities = $citiesQuery->paginate(
            (int) ($request->query('city_limit', '50')),
            ['*'],
            'city_page',
            (int) ($request->query('city_page', '1')),
        );

        return Inertia::render('Locations/Index', [
            'cantons' => $cantons,
            'cities' => [
                'data' => $cities->map(fn (City $c) => [
                    'id' => $c->id,
                    'canton_id' => $c->canton_id,
                    'name' => $c->getTranslations('name'),
                    'postal_code' => $c->postal_code,
                    'image_url' => $c->image_url,
                    'is_active' => $c->is_active,
                    'canton' => $c->canton ? [
                        'id' => $c->canton->id,
                        'code' => $c->canton->code,
                    ] : null,
                ]),
                'meta' => [
                    'current_page' => $cities->currentPage(),
                    'from' => $cities->firstItem(),
                    'last_page' => $cities->lastPage(),
                    'per_page' => $cities->perPage(),
                    'to' => $cities->lastItem(),
                    'total' => $cities->total(),
                ],
            ],
            'filters' => $request->only(['canton_id', 'city_search']),
        ]);
    }

    // ── Cantons ──

    public function storeCanton(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|size:2|unique:cantons,code',
            'name' => 'required|array',
            'name.en' => 'required|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $validated['code'] = strtoupper($validated['code']);
        Canton::create($validated);

        return redirect()->back()->with('success', __('locations.canton_created'));
    }

    public function updateCanton(Request $request, Canton $canton): RedirectResponse
    {
        $validated = $request->validate([
            'code' => "sometimes|string|size:2|unique:cantons,code,{$canton->id}",
            'name' => 'sometimes|array',
            'name.en' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($validated['code'])) {
            $validated['code'] = strtoupper($validated['code']);
        }

        $canton->update($validated);

        return redirect()->back()->with('success', __('locations.canton_updated'));
    }

    public function destroyCanton(Canton $canton): RedirectResponse
    {
        $canton->delete();

        return redirect()->back()->with('success', __('locations.canton_deleted'));
    }

    // ── Cities ──

    public function storeCity(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'canton_id' => 'required|integer|exists:cantons,id',
            'name' => 'required|array',
            'name.en' => 'required|string',
            'postal_code' => 'required|string|max:10',
            'image_url' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ]);

        City::create($validated);

        return redirect()->back()->with('success', __('locations.city_created'));
    }

    public function updateCity(Request $request, City $city): RedirectResponse
    {
        $validated = $request->validate([
            'canton_id' => 'sometimes|integer|exists:cantons,id',
            'name' => 'sometimes|array',
            'name.en' => 'sometimes|string',
            'postal_code' => 'sometimes|string|max:10',
            'image_url' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ]);

        $city->update($validated);

        return redirect()->back()->with('success', __('locations.city_updated'));
    }

    public function destroyCity(City $city): RedirectResponse
    {
        $city->delete();

        return redirect()->back()->with('success', __('locations.city_deleted'));
    }
}
