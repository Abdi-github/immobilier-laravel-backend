<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Domain\Property\Models\PropertyTranslation;
use App\Domain\Translation\Enums\ApprovalStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyTranslationResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TranslationController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('translations:read');

        $paginator = PropertyTranslation::query()
            ->when($request->query('property_id'), fn ($q, $id) => $q->where('property_id', $id))
            ->when($request->query('language'), fn ($q, $l) => $q->where('language', $l))
            ->when($request->query('source'), fn ($q, $s) => $q->where('source', $s))
            ->when($request->query('approval_status'), fn ($q, $s) => $q->where('approval_status', $s))
            ->when($request->query('approved_by'), fn ($q, $id) => $q->where('approved_by', $id))
            ->when($request->query('search'), function ($q, $search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('title', 'ILIKE', "%{$search}%")
                        ->orWhere('description', 'ILIKE', "%{$search}%");
                });
            })
            ->with(['property:id,external_id,source_language', 'approvedByUser:id,first_name,last_name'])
            ->orderByDesc('created_at')
            ->paginate(
                $request->integer('limit', 20),
                ['*'],
                'page',
                $request->integer('page', 1),
            );

        $paginator->through(fn ($t) => $this->formatTranslation($t));

        return $this->paginatedResponse($paginator, __('translations.list_retrieved'));
    }

    public function pending(Request $request): JsonResponse
    {
        $this->authorize('translations:read');

        $paginator = PropertyTranslation::query()
            ->where('approval_status', ApprovalStatus::PENDING)
            ->when($request->query('language'), fn ($q, $l) => $q->where('language', $l))
            ->when($request->query('source'), fn ($q, $s) => $q->where('source', $s))
            ->with(['property:id,external_id,source_language', 'approvedByUser:id,first_name,last_name'])
            ->orderByDesc('created_at')
            ->paginate(
                $request->integer('limit', 20),
                ['*'],
                'page',
                $request->integer('page', 1),
            );

        $paginator->through(fn ($t) => $this->formatTranslation($t));

        return $this->paginatedResponse($paginator, __('translations.pending_retrieved'));
    }

    public function statistics(): JsonResponse
    {
        $this->authorize('translations:read');

        $total = PropertyTranslation::count();
        $byStatus = [];
        foreach (ApprovalStatus::cases() as $status) {
            $byStatus[$status->value] = PropertyTranslation::where('approval_status', $status)->count();
        }

        $byLanguage = PropertyTranslation::selectRaw('language, COUNT(*) as count')
            ->groupBy('language')
            ->pluck('count', 'language');

        $bySource = PropertyTranslation::selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->pluck('count', 'source');

        return $this->successResponse([
            'total' => $total,
            'by_status' => $byStatus,
            'by_language' => $byLanguage,
            'by_source' => $bySource,
        ], __('translations.statistics_retrieved'));
    }

    public function show(int $id): JsonResponse
    {
        $this->authorize('translations:read');

        $translation = PropertyTranslation::with([
            'property:id,external_id,source_language',
            'approvedByUser:id,first_name,last_name',
        ])->find($id);

        if (! $translation) {
            return $this->notFoundResponse(__('translations.not_found'));
        }

        return $this->successResponse($this->formatTranslation($translation), __('translations.retrieved'));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('translations:create');

        $validated = $request->validate([
            'property_id' => 'required|integer|exists:properties,id',
            'language' => 'required|string|in:en,fr,de,it',
            'title' => 'required|string|max:300',
            'description' => 'required|string',
            'source' => 'nullable|string|in:original,deepl,libretranslate,human',
            'quality_score' => 'nullable|integer|min:0|max:100',
        ]);

        // Check uniqueness
        $exists = PropertyTranslation::where('property_id', $validated['property_id'])
            ->where('language', $validated['language'])
            ->exists();

        if ($exists) {
            return $this->errorResponse(
                __('translations.already_exists'),
                409,
                'TRANSLATION_EXISTS',
            );
        }

        $validated['source'] = $validated['source'] ?? 'human';
        $validated['approval_status'] = ApprovalStatus::PENDING->value;

        $translation = PropertyTranslation::create($validated);
        $translation->load(['property:id,external_id,source_language', 'approvedByUser:id,first_name,last_name']);

        return $this->createdResponse($this->formatTranslation($translation), __('translations.created'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorize('translations:create');

        $translation = PropertyTranslation::find($id);
        if (! $translation) {
            return $this->notFoundResponse(__('translations.not_found'));
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:300',
            'description' => 'nullable|string',
            'source' => 'nullable|string|in:original,deepl,libretranslate,human',
            'quality_score' => 'nullable|integer|min:0|max:100',
        ]);

        $translation->update($validated);
        $translation->load(['property:id,external_id,source_language', 'approvedByUser:id,first_name,last_name']);

        return $this->successResponse($this->formatTranslation($translation), __('translations.updated'));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->authorize('translations:create');

        $translation = PropertyTranslation::find($id);
        if (! $translation) {
            return $this->notFoundResponse(__('translations.not_found'));
        }

        $translation->delete();

        return $this->successResponse(null, __('translations.deleted'));
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $this->authorize('translations:approve');

        $translation = PropertyTranslation::find($id);
        if (! $translation) {
            return $this->notFoundResponse(__('translations.not_found'));
        }

        $translation->update([
            'approval_status' => ApprovalStatus::APPROVED,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        $translation->load(['property:id,external_id,source_language', 'approvedByUser:id,first_name,last_name']);

        return $this->successResponse($this->formatTranslation($translation), __('translations.approved'));
    }

    public function bulkApprove(Request $request): JsonResponse
    {
        $this->authorize('translations:approve');

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:property_translations,id',
        ]);

        $translations = PropertyTranslation::whereIn('id', $request->input('ids'))->get();

        foreach ($translations as $translation) {
            $translation->update([
                'approval_status' => ApprovalStatus::APPROVED,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'rejection_reason' => null,
            ]);
        }

        $translations->load(['property:id,external_id,source_language', 'approvedByUser:id,first_name,last_name']);

        return $this->successResponse(
            $translations->map(fn ($t) => $this->formatTranslation($t)),
            __('translations.bulk_approved'),
        );
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $this->authorize('translations:approve');

        $translation = PropertyTranslation::find($id);
        if (! $translation) {
            return $this->notFoundResponse(__('translations.not_found'));
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $translation->update([
            'approval_status' => ApprovalStatus::REJECTED,
            'approved_by' => $request->user()->id,
            'approved_at' => null,
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        $translation->load(['property:id,external_id,source_language', 'approvedByUser:id,first_name,last_name']);

        return $this->successResponse($this->formatTranslation($translation), __('translations.rejected'));
    }

    public function reset(int $id): JsonResponse
    {
        $this->authorize('translations:approve');

        $translation = PropertyTranslation::find($id);
        if (! $translation) {
            return $this->notFoundResponse(__('translations.not_found'));
        }

        $translation->update([
            'approval_status' => ApprovalStatus::PENDING,
            'approved_by' => null,
            'approved_at' => null,
            'rejection_reason' => null,
        ]);

        $translation->load(['property:id,external_id,source_language', 'approvedByUser:id,first_name,last_name']);

        return $this->successResponse($this->formatTranslation($translation), __('translations.reset'));
    }

    public function byProperty(Request $request, int $propertyId): JsonResponse
    {
        $this->authorize('translations:read');

        $translations = PropertyTranslation::where('property_id', $propertyId)
            ->with(['approvedByUser:id,first_name,last_name'])
            ->orderBy('language')
            ->get();

        $allLanguages = ['en', 'fr', 'de', 'it'];
        $existingLanguages = $translations->pluck('language')->toArray();
        $missingLanguages = array_values(array_diff($allLanguages, $existingLanguages));

        $allApproved = $translations->isNotEmpty()
            && $translations->every(fn ($t) => $t->approval_status === ApprovalStatus::APPROVED);

        return $this->successResponse([
            'translations' => $translations->map(fn ($t) => $this->formatTranslation($t)),
            'missing_languages' => $missingLanguages,
            'all_approved' => $allApproved,
        ], __('translations.property_translations_retrieved'));
    }

    private function formatTranslation(PropertyTranslation $translation): array
    {
        $data = (new PropertyTranslationResource($translation))->resolve();

        if ($translation->relationLoaded('property') && $translation->property) {
            $data['property'] = [
                'id' => (string) $translation->property->id,
                'external_id' => $translation->property->external_id,
                'source_language' => $translation->property->source_language,
            ];
        }

        if ($translation->relationLoaded('approvedByUser') && $translation->approvedByUser) {
            $data['approver'] = [
                'id' => (string) $translation->approvedByUser->id,
                'first_name' => $translation->approvedByUser->first_name,
                'last_name' => $translation->approvedByUser->last_name,
            ];
        }

        return $data;
    }
}
