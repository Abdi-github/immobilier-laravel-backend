<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domain\Agency\Models\Agency;
use App\Http\Controllers\Controller;
use App\Http\Resources\AgencyResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class AgencyController extends Controller
{
    use ApiResponse;

    public function statistics(): JsonResponse
    {
        // \Log::debug('Agency statistics requested');
        
        $this->authorize('agencies:read');

        $totalAgencies = Agency::count();
        $verifiedAgencies = Agency::where('is_verified', true)->count();

        $byStatus = Agency::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $byCanton = Agency::selectRaw('canton_id, COUNT(*) as count')
            ->groupBy('canton_id')
            ->pluck('count', 'canton_id');

        $recentAgencies = Agency::where('created_at', '>=', now()->subDays(30))->count();
        // \Log::debug('Stats computed', compact('totalAgencies', 'verifiedAgencies', 'recentAgencies'));

        return $this->successResponse([
            'total_agencies' => $totalAgencies,
            'verified_agencies' => $verifiedAgencies,
            'by_status' => $byStatus,
            'by_canton' => $byCanton,
            'recent_agencies' => $recentAgencies,
        ], __('agencies.statistics_retrieved'));
    }

    public function index(Request $request): JsonResponse
    {
        // \Log::debug('Listing agencies', ['search' => !!$request->query('search')]);
        
        $this->authorize('agencies:read');

        $query = Agency::query()
            ->when($request->query('search'), fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'ILIKE', "%{$s}%")
                    ->orWhere('email', 'ILIKE', "%{$s}%")
                    ->orWhere('address', 'ILIKE', "%{$s}%");
            }))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('canton_id'), fn ($q, $id) => $q->where('canton_id', $id))
            ->when($request->query('city_id'), fn ($q, $id) => $q->where('city_id', $id))
            ->when($request->has('is_verified'), fn ($q) => $q->where('is_verified', filter_var($request->query('is_verified'), FILTER_VALIDATE_BOOLEAN)))
            ->with(['city', 'canton']);

        $sort = $request->query('sort', 'created_at');
        $order = $request->query('order', 'desc');
        $query->orderBy($sort, $order);
        // \Log::debug('Query built, sorting by:', compact('sort', 'order'));

        $agencies = $query->paginate(
            (int) $request->query('limit', '20'),
            ['*'],
            'page',
            (int) $request->query('page', '1'),
        );
        // \Log::debug('Results:', ['count' => $agencies->count(), 'total' => $agencies->total()]);

        return $this->paginatedResponse(
            $agencies->through(fn ($a) => new AgencyResource($a)),
            __('agencies.agencies_listed'),
        );
    }

    public function show(int $id): JsonResponse
    {
        $this->authorize('agencies:read');

        $agency = Agency::with(['city', 'canton'])->find($id);
        if (! $agency) {
            return $this->notFoundResponse(__('agencies.not_found'));
        }

        return $this->successResponse(new AgencyResource($agency), __('agencies.agency_retrieved'));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('agencies:create');

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
            'is_verified' => 'sometimes|boolean',
        ]);

        // Auto-generate slug
        $validated['slug'] = Str::slug($validated['name']);
        $baseSlug = $validated['slug'];
        $counter = 1;
        while (Agency::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = "{$baseSlug}-{$counter}";
            $counter++;
        }

        if (! empty($validated['is_verified']) && $validated['is_verified']) {
            $validated['verification_date'] = now();
        }

        $agency = Agency::create($validated);
        $agency->load(['city', 'canton']);

        return $this->createdResponse(new AgencyResource($agency), __('agencies.agency_created'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorize('agencies:manage');

        $agency = Agency::find($id);
        if (! $agency) {
            return $this->notFoundResponse(__('agencies.not_found'));
        }

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

        // Regenerate slug if name changed
        if (isset($validated['name']) && $validated['name'] !== $agency->name) {
            $slug = Str::slug($validated['name']);
            $baseSlug = $slug;
            $counter = 1;
            while (Agency::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }
            $validated['slug'] = $slug;
        }

        $agency->update($validated);
        $agency->load(['city', 'canton']);

        return $this->successResponse(new AgencyResource($agency), __('agencies.agency_updated'));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->authorize('agencies:delete');

        $agency = Agency::find($id);
        if (! $agency) {
            return $this->notFoundResponse(__('agencies.not_found'));
        }

        $agency->delete();

        return $this->successResponse(null, __('agencies.agency_deleted'));
    }

    public function verify(int $id): JsonResponse
    {
        $this->authorize('agencies:manage');

        $agency = Agency::find($id);
        if (! $agency) {
            return $this->notFoundResponse(__('agencies.not_found'));
        }

        $agency->update([
            'is_verified' => true,
            'verification_date' => now(),
        ]);

        $agency->load(['city', 'canton']);

        return $this->successResponse(new AgencyResource($agency), __('agencies.agency_verified'));
    }

    public function unverify(int $id): JsonResponse
    {
        $this->authorize('agencies:manage');

        $agency = Agency::find($id);
        if (! $agency) {
            return $this->notFoundResponse(__('agencies.not_found'));
        }

        $agency->update([
            'is_verified' => false,
            'verification_date' => null,
        ]);

        $agency->load(['city', 'canton']);

        return $this->successResponse(new AgencyResource($agency), __('agencies.agency_unverified'));
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $this->authorize('agencies:manage');

        $agency = Agency::find($id);
        if (! $agency) {
            return $this->notFoundResponse(__('agencies.not_found'));
        }

        $validated = $request->validate([
            'status' => 'required|string|in:active,inactive,suspended',
        ]);

        $agency->update(['status' => $validated['status']]);
        $agency->load(['city', 'canton']);

        return $this->successResponse(new AgencyResource($agency), __('agencies.status_updated'));
    }
}
