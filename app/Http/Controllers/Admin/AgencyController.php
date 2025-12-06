<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Agency\Models\Agency;
use App\Domain\Location\Models\Canton;
use App\Domain\Location\Models\City;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class AgencyController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only([
            'page', 'limit', 'sort', 'order', 'search',
            'status', 'canton_id', 'is_verified',
        ]);

        $query = Agency::query()
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'ILIKE', "%{$s}%")
                    ->orWhere('email', 'ILIKE', "%{$s}%")
                    ->orWhere('address', 'ILIKE', "%{$s}%");
            }))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['canton_id'] ?? null, fn ($q, $id) => $q->where('canton_id', $id))
            ->when(isset($filters['is_verified']) && $filters['is_verified'] !== '', fn ($q) => $q->where('is_verified', filter_var($filters['is_verified'], FILTER_VALIDATE_BOOLEAN)))
            ->with(['canton', 'city'])
            ->withCount(['members', 'properties']);

        $sort = $filters['sort'] ?? 'created_at';
        $order = $filters['order'] ?? 'desc';
        $query->orderBy($sort, $order);

        $agencies = $query->paginate(
            (int) ($filters['limit'] ?? 20),
            ['*'],
            'page',
            (int) ($filters['page'] ?? 1),
        );

        return Inertia::render('Agencies/Index', [
            'agencies' => $this->paginateToArray($agencies),
            'filters' => $filters,
            'cantons' => fn () => Canton::where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function show(Agency $agency): Response
    {
        $agency->load(['canton', 'city']);
        $agency->loadCount(['members', 'properties', 'leads']);

        return Inertia::render('Agencies/Show', [
            'agency' => $this->agencyToArray($agency),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'description' => 'nullable|array',
            'logo_url' => 'nullable|string|max:500',
            'website' => 'nullable|string|max:500',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:200',
            'address' => 'required|string|max:500',
            'city_id' => 'required|integer|exists:cities,id',
            'canton_id' => 'required|integer|exists:cantons,id',
            'postal_code' => 'nullable|string|max:10',
            'status' => 'sometimes|string|in:active,inactive,suspended',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $baseSlug = $validated['slug'];
        $counter = 1;
        while (Agency::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = "{$baseSlug}-{$counter}";
            $counter++;
        }

        Agency::create($validated);

        return redirect()
            ->route('admin.agencies.index')
            ->with('success', __('agencies.agency_created'));
    }

    public function update(Request $request, Agency $agency): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:200',
            'description' => 'nullable|array',
            'logo_url' => 'nullable|string|max:500',
            'website' => 'nullable|string|max:500',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:200',
            'address' => 'sometimes|string|max:500',
            'city_id' => 'sometimes|integer|exists:cities,id',
            'canton_id' => 'sometimes|integer|exists:cantons,id',
            'postal_code' => 'nullable|string|max:10',
            'status' => 'sometimes|string|in:active,inactive,suspended',
        ]);

        if (isset($validated['name']) && $validated['name'] !== $agency->name) {
            $slug = Str::slug($validated['name']);
            $baseSlug = $slug;
            $counter = 1;
            while (Agency::where('slug', $slug)->where('id', '!=', $agency->id)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }
            $validated['slug'] = $slug;
        }

        $agency->update($validated);

        return redirect()->back()->with('success', __('agencies.agency_updated'));
    }

    public function destroy(Agency $agency): RedirectResponse
    {
        $agency->delete();

        return redirect()
            ->route('admin.agencies.index')
            ->with('success', __('agencies.agency_deleted'));
    }

    public function verify(Agency $agency): RedirectResponse
    {
        $agency->update([
            'is_verified' => true,
            'verification_date' => now(),
        ]);

        return redirect()->back()->with('success', __('agencies.agency_verified'));
    }

    public function unverify(Agency $agency): RedirectResponse
    {
        $agency->update([
            'is_verified' => false,
            'verification_date' => null,
        ]);

        return redirect()->back()->with('success', __('agencies.agency_unverified'));
    }

    public function updateStatus(Request $request, Agency $agency): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:active,inactive,suspended',
        ]);

        $agency->update(['status' => $validated['status']]);

        return redirect()->back()->with('success', __('agencies.status_updated'));
    }

    // ── Helpers ──

    private function paginateToArray($paginator): array
    {
        return [
            'data' => collect($paginator->items())->map(fn ($a) => $this->agencyToArray($a)),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ];
    }

    private function agencyToArray(Agency $a): array
    {
        return [
            'id' => $a->id,
            'name' => $a->name,
            'slug' => $a->slug,
            'description' => $a->getTranslations('description'),
            'logo_url' => $a->logo_url,
            'website' => $a->website,
            'email' => $a->email,
            'phone' => $a->phone,
            'contact_person' => $a->contact_person,
            'address' => $a->address,
            'city_id' => $a->city_id,
            'canton_id' => $a->canton_id,
            'postal_code' => $a->postal_code,
            'status' => $a->status,
            'is_verified' => $a->is_verified,
            'verification_date' => $a->verification_date?->toISOString(),
            'total_properties' => $a->total_properties,
            'created_at' => $a->created_at?->toISOString(),
            'updated_at' => $a->updated_at?->toISOString(),
            'members_count' => $a->members_count ?? 0,
            'properties_count' => $a->properties_count ?? 0,
            'leads_count' => $a->leads_count ?? 0,
            'canton' => $a->relationLoaded('canton') && $a->canton ? [
                'id' => $a->canton->id,
                'code' => $a->canton->code,
                'name' => $a->canton->getTranslation('name', app()->getLocale()),
            ] : null,
            'city' => $a->relationLoaded('city') && $a->city ? [
                'id' => $a->city->id,
                'name' => $a->city->getTranslation('name', app()->getLocale()),
                'postal_code' => $a->city->postal_code,
            ] : null,
        ];
    }
}
