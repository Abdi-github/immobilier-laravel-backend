<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\PublicApi;

use App\Domain\Translation\Enums\ApprovalStatus;
use App\Domain\Property\Models\Property;
use App\Domain\Property\Models\PropertyTranslation;
use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyTranslationResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

final class PropertyTranslationController extends Controller
{
    use ApiResponse;

    public function index(int $id): JsonResponse
    {
        $property = Property::find($id);

        if (! $property || ! $property->isPublished()) {
            return $this->notFoundResponse(__('properties.not_found'));
        }

        $translations = PropertyTranslation::where('property_id', $id)
            ->where('approval_status', ApprovalStatus::APPROVED)
            ->get();

        return $this->successResponse(
            PropertyTranslationResource::collection($translations),
            __('common.retrieved'),
        );
    }

    public function show(int $id, string $language): JsonResponse
    {
        $property = Property::find($id);

        if (! $property || ! $property->isPublished()) {
            return $this->notFoundResponse(__('properties.not_found'));
        }

        $translation = PropertyTranslation::where('property_id', $id)
            ->where('language', $language)
            ->where('approval_status', ApprovalStatus::APPROVED)
            ->first();

        if (! $translation) {
            return $this->notFoundResponse(__('translations.not_found'));
        }

        return $this->successResponse(
            new PropertyTranslationResource($translation),
            __('common.retrieved'),
        );
    }
}
