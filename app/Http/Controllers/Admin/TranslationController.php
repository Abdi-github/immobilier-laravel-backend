<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Property\Models\PropertyTranslation;
use App\Domain\Translation\Enums\ApprovalStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TranslationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('translations:read');

        $paginator = PropertyTranslation::query()
            ->when($request->query('language'), fn ($q, $l) => $q->where('language', $l))
            ->when($request->query('source'), fn ($q, $s) => $q->where('source', $s))
            ->when($request->query('approval_status'), fn ($q, $s) => $q->where('approval_status', $s))
            ->when($request->query('search'), function ($q, $search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('title', 'ILIKE', "%{$search}%")
                        ->orWhere('description', 'ILIKE', "%{$search}%");
                });
            })
            ->with(['property:id,external_id,source_language', 'approvedByUser:id,first_name,last_name'])
            ->orderByDesc('created_at')
            ->paginate($request->integer('limit', 20));

        // Statistics
        $total = PropertyTranslation::count();
        $byStatus = [];
        foreach (ApprovalStatus::cases() as $status) {
            $byStatus[$status->value] = PropertyTranslation::where('approval_status', $status)->count();
        }

        return Inertia::render('Translations/Index', [
            'translations' => [
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'from' => $paginator->firstItem(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'to' => $paginator->lastItem(),
                    'total' => $paginator->total(),
                ],
            ],
            'filters' => $request->only(['search', 'language', 'source', 'approval_status']),
            'stats' => [
                'total' => $total,
                'by_status' => $byStatus,
            ],
        ]);
    }

    public function approve(Request $request, int $id): RedirectResponse
    {
        $this->authorize('translations:approve');

        $translation = PropertyTranslation::findOrFail($id);
        $translation->update([
            'approval_status' => ApprovalStatus::APPROVED,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        return redirect()->back()->with('success', 'Translation approved.');
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        $this->authorize('translations:approve');

        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $translation = PropertyTranslation::findOrFail($id);
        $translation->update([
            'approval_status' => ApprovalStatus::REJECTED,
            'approved_by' => $request->user()->id,
            'approved_at' => null,
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        return redirect()->back()->with('success', 'Translation rejected.');
    }

    public function reset(int $id): RedirectResponse
    {
        $this->authorize('translations:approve');

        $translation = PropertyTranslation::findOrFail($id);
        $translation->update([
            'approval_status' => ApprovalStatus::PENDING,
            'approved_by' => null,
            'approved_at' => null,
            'rejection_reason' => null,
        ]);

        return redirect()->back()->with('success', 'Translation reset to pending.');
    }

    public function bulkApprove(Request $request): RedirectResponse
    {
        $this->authorize('translations:approve');

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:property_translations,id',
        ]);

        PropertyTranslation::whereIn('id', $request->input('ids'))->update([
            'approval_status' => ApprovalStatus::APPROVED->value,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        return redirect()->back()->with('success', count($request->input('ids')) . ' translations approved.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->authorize('translations:create');

        PropertyTranslation::findOrFail($id)->delete();

        return redirect()->back()->with('success', 'Translation deleted.');
    }
}
