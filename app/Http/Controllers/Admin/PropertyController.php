<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Catalog\Models\Amenity;
use App\Domain\Catalog\Models\Category;
use App\Domain\Location\Models\Canton;
use App\Domain\Location\Models\City;
use App\Domain\Property\Enums\PropertyStatus;
use App\Domain\Property\Models\Property;
use App\Domain\Property\Services\PropertyService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PropertyController extends Controller
{
    public function __construct(
        private readonly PropertyService $propertyService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only([
            'page', 'limit', 'sort', 'order', 'search',
            'status', 'canton_id', 'city_id',
            'category_id', 'transaction_type', 'agency_id',
        ]);

        $properties = $this->propertyService->getAllProperties($filters);

        return Inertia::render('Properties/Index', [
            'properties' => $this->paginateToArray($properties),
            'filters' => $filters,
            'statuses' => fn () => array_map(
                fn (PropertyStatus $s) => ['value' => $s->value, 'label' => $s->label()],
                PropertyStatus::cases(),
            ),
            'categories' => fn () => Category::orderBy('sort_order')->get(['id', 'name', 'section']),
            'cantons' => fn () => Canton::where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function pending(Request $request): Response
    {
        $filters = array_merge(
            $request->only(['page', 'limit', 'sort', 'order', 'search']),
            ['status' => PropertyStatus::PENDING_APPROVAL->value],
        );

        $properties = $this->propertyService->getAllProperties($filters);

        return Inertia::render('Properties/Pending', [
            'properties' => $this->paginateToArray($properties),
        ]);
    }

    public function show(Property $property): Response
    {
        $property = $this->propertyService->getPropertyById($property->id);

        return Inertia::render('Properties/Show', [
            'property' => $this->propertyToArray($property),
            'images' => fn () => $this->propertyService->getPropertyImages($property->id)
                ->map(fn ($img) => $this->imageToArray($img)),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Properties/Create', [
            'categories' => Category::orderBy('sort_order')->get(['id', 'name', 'section']),
            'amenities' => Amenity::where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'group']),
            'cantons' => Canton::where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'cities' => City::where('is_active', true)->orderBy('name')->get(['id', 'canton_id', 'name', 'postal_code']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
            'agency_id' => 'nullable|integer|exists:agencies,id',
            'transaction_type' => 'required|string|in:rent,buy',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'additional_costs' => 'nullable|numeric|min:0',
            'rooms' => 'nullable|numeric|min:0',
            'surface' => 'nullable|numeric|min:0',
            'address' => 'required|string|max:500',
            'city_id' => 'required|integer|exists:cities,id',
            'canton_id' => 'required|integer|exists:cantons,id',
            'postal_code' => 'nullable|string|max:10',
            'proximity' => 'nullable|array',
            'amenities' => 'nullable|array',
            'amenities.*' => 'integer|exists:amenities,id',
            'status' => 'nullable|string|in:DRAFT,PENDING_APPROVAL',
        ]);

        $validated['owner_id'] = $request->user()->id;

        $property = $this->propertyService->createProperty($validated);

        return redirect()
            ->route('admin.properties.show', $property->id)
            ->with('success', __('properties.created'));
    }

    public function edit(Property $property): Response
    {
        $property = $this->propertyService->getPropertyById($property->id);

        return Inertia::render('Properties/Edit', [
            'property' => $this->propertyToArray($property),
            'categories' => Category::orderBy('sort_order')->get(['id', 'name', 'section']),
            'amenities' => Amenity::where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'group']),
            'cantons' => Canton::where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
            'cities' => City::where('is_active', true)->orderBy('name')->get(['id', 'canton_id', 'name', 'postal_code']),
        ]);
    }

    public function update(Request $request, Property $property): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => 'nullable|integer|exists:categories,id',
            'agency_id' => 'nullable|integer|exists:agencies,id',
            'transaction_type' => 'nullable|string|in:rent,buy',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'additional_costs' => 'nullable|numeric|min:0',
            'rooms' => 'nullable|numeric|min:0',
            'surface' => 'nullable|numeric|min:0',
            'address' => 'nullable|string|max:500',
            'city_id' => 'nullable|integer|exists:cities,id',
            'canton_id' => 'nullable|integer|exists:cantons,id',
            'postal_code' => 'nullable|string|max:10',
            'proximity' => 'nullable|array',
            'amenities' => 'nullable|array',
            'amenities.*' => 'integer|exists:amenities,id',
        ]);

        $this->propertyService->updateProperty($property->id, $validated);

        return redirect()
            ->route('admin.properties.show', $property->id)
            ->with('success', __('properties.updated'));
    }

    public function destroy(Property $property): RedirectResponse
    {
        $this->propertyService->deleteProperty($property->id);

        return redirect()
            ->route('admin.properties.index')
            ->with('success', __('properties.deleted'));
    }

    // ── Status Workflow ──

    public function submit(Property $property): RedirectResponse
    {
        $this->propertyService->submitForApproval($property->id);

        return redirect()->back()->with('success', __('properties.submitted'));
    }

    public function approve(Request $request, Property $property): RedirectResponse
    {
        $this->propertyService->approve($property->id, $request->user()->id);

        return redirect()->back()->with('success', __('properties.approved'));
    }

    public function reject(Request $request, Property $property): RedirectResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $this->propertyService->reject(
            $property->id,
            $request->user()->id,
            $request->input('rejection_reason'),
        );

        return redirect()->back()->with('success', __('properties.rejected'));
    }

    public function publish(Property $property): RedirectResponse
    {
        $this->propertyService->publish($property->id);

        return redirect()->back()->with('success', __('properties.published'));
    }

    public function archive(Property $property): RedirectResponse
    {
        $this->propertyService->archive($property->id);

        return redirect()->back()->with('success', __('properties.archived'));
    }

    // ── Image Management ──

    public function uploadImage(Request $request, Property $property): RedirectResponse
    {
        $validated = $request->validate([
            'url' => 'required|string|max:1000',
            'secure_url' => 'nullable|string|max:1000',
            'thumbnail_url' => 'nullable|string|max:1000',
            'alt_text' => 'nullable|string|max:500',
            'caption' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'is_primary' => 'nullable|boolean',
        ]);

        $this->propertyService->addImage($property->id, $validated);

        return redirect()->back()->with('success', __('properties.image_uploaded'));
    }

    public function setPrimaryImage(Property $property, int $imageId): RedirectResponse
    {
        $this->propertyService->updateImage($property->id, $imageId, ['is_primary' => true]);

        return redirect()->back()->with('success', __('properties.primary_image_set'));
    }

    public function deleteImage(Property $property, int $imageId): RedirectResponse
    {
        $this->propertyService->deleteImage($property->id, $imageId);

        return redirect()->back()->with('success', __('properties.image_deleted'));
    }

    public function reorderImages(Request $request, Property $property): RedirectResponse
    {
        $request->validate([
            'image_ids' => 'required|array',
            'image_ids.*' => 'integer',
        ]);

        $this->propertyService->reorderImages($property->id, $request->input('image_ids'));

        return redirect()->back()->with('success', __('properties.images_reordered'));
    }

    // ── Helpers ──

    private function paginateToArray($paginator): array
    {
        return [
            'data' => collect($paginator->items())->map(fn ($p) => $this->propertyToArray($p)),
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

    private function propertyToArray(Property $p): array
    {
        $translation = $p->relationLoaded('translations')
            ? ($p->translations->firstWhere('language', app()->getLocale())
                ?? $p->translations->firstWhere('language', $p->source_language ?? 'en')
                ?? $p->translations->first())
            : null;

        return [
            'id' => $p->id,
            'external_id' => $p->external_id,
            'external_url' => $p->external_url,
            'source_language' => $p->source_language,
            'title' => $translation?->title ?? $p->external_id,
            'description' => $translation?->description,
            'category_id' => $p->category_id,
            'agency_id' => $p->agency_id,
            'owner_id' => $p->owner_id,
            'transaction_type' => $p->transaction_type->value,
            'price' => (float) $p->price,
            'currency' => $p->currency ?? 'CHF',
            'additional_costs' => $p->additional_costs ? (float) $p->additional_costs : null,
            'rooms' => $p->rooms ? (float) $p->rooms : null,
            'surface' => $p->surface ? (float) $p->surface : null,
            'address' => $p->address,
            'city_id' => $p->city_id,
            'canton_id' => $p->canton_id,
            'postal_code' => $p->postal_code,
            'proximity' => $p->proximity,
            'status' => $p->status->value,
            'reviewed_by' => $p->reviewed_by,
            'reviewed_at' => $p->reviewed_at?->toISOString(),
            'rejection_reason' => $p->rejection_reason,
            'published_at' => $p->published_at?->toISOString(),
            'created_at' => $p->created_at?->toISOString(),
            'updated_at' => $p->updated_at?->toISOString(),
            'category' => $p->relationLoaded('category') && $p->category ? [
                'id' => $p->category->id,
                'name' => $p->category->getTranslation('name', app()->getLocale()),
                'section' => $p->category->section,
            ] : null,
            'canton' => $p->relationLoaded('canton') && $p->canton ? [
                'id' => $p->canton->id,
                'code' => $p->canton->code,
                'name' => $p->canton->getTranslation('name', app()->getLocale()),
            ] : null,
            'city' => $p->relationLoaded('city') && $p->city ? [
                'id' => $p->city->id,
                'name' => $p->city->getTranslation('name', app()->getLocale()),
                'postal_code' => $p->city->postal_code,
            ] : null,
            'owner' => $p->relationLoaded('owner') && $p->owner ? [
                'id' => $p->owner->id,
                'first_name' => $p->owner->first_name,
                'last_name' => $p->owner->last_name,
            ] : null,
            'agency' => $p->relationLoaded('agency') && $p->agency ? [
                'id' => $p->agency->id,
                'name' => $p->agency->name,
            ] : null,
            'reviewer' => $p->relationLoaded('reviewer') && $p->reviewer ? [
                'id' => $p->reviewer->id,
                'first_name' => $p->reviewer->first_name,
                'last_name' => $p->reviewer->last_name,
            ] : null,
            'amenities' => $p->relationLoaded('amenities')
                ? $p->amenities->map(fn ($a) => [
                    'id' => $a->id,
                    'name' => $a->getTranslation('name', app()->getLocale()),
                    'group' => $a->group,
                    'icon' => $a->icon,
                ])->toArray()
                : [],
            'primary_image' => $p->relationLoaded('primaryImage') && $p->primaryImage
                ? $this->imageToArray($p->primaryImage)
                : null,
            'images' => $p->relationLoaded('images')
                ? $p->images->map(fn ($img) => $this->imageToArray($img))->toArray()
                : [],
        ];
    }

    private function imageToArray($img): array
    {
        return [
            'id' => $img->id,
            'property_id' => $img->property_id,
            'url' => $img->url,
            'secure_url' => $img->secure_url,
            'thumbnail_url' => $img->thumbnail_url,
            'thumbnail_secure_url' => $img->thumbnail_secure_url,
            'public_id' => $img->public_id,
            'width' => $img->width,
            'height' => $img->height,
            'format' => $img->format,
            'bytes' => $img->bytes,
            'alt_text' => $img->alt_text,
            'caption' => $img->caption,
            'sort_order' => $img->sort_order,
            'is_primary' => $img->is_primary,
            'source' => $img->source?->value,
            'original_filename' => $img->original_filename,
        ];
    }
}
