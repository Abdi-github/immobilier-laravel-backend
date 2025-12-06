<?php

declare(strict_types=1);

namespace App\Domain\Lead\Services;

use App\Domain\Lead\Events\LeadCreated;
use App\Domain\Lead\Models\Lead;
use App\Domain\Lead\Repositories\LeadRepositoryInterface;
use App\Domain\Property\Enums\PropertyStatus;
use App\Domain\Property\Models\Property;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class LeadService
{
    public function __construct(
        private readonly LeadRepositoryInterface $leadRepository,
    ) {}

    public function createPublicLead(array $data): Lead
    {
        $property = Property::findOrFail($data['property_id']);

        if ($property->status !== PropertyStatus::PUBLISHED) {
            throw new \App\Domain\Shared\Exceptions\DomainException(
                __('common.property_not_published'),
                422,
            );
        }

        // Duplicate check: same email + same property within 7 days
        $duplicate = Lead::where('contact_email', $data['contact_email'])
            ->where('property_id', $data['property_id'])
            ->where('created_at', '>=', now()->subDays(7))
            ->exists();

        if ($duplicate) {
            throw new \App\Domain\Shared\Exceptions\DomainException(
                __('common.duplicate_lead'),
                409,
            );
        }

        // Auto-assign agency from property
        $data['agency_id'] = $property->agency_id;
        $data['status'] = 'NEW';
        $data['priority'] = $data['priority'] ?? 'medium';
        $data['source'] = $data['source'] ?? 'website';

        $lead = $this->leadRepository->create($data);

        LeadCreated::dispatch($lead);

        return $lead;
    }

    public function createAuthenticatedLead(array $data, \App\Domain\User\Models\User $user): Lead
    {
        $data['user_id'] = $user->id;
        $data['contact_first_name'] = $data['contact_first_name'] ?? $user->first_name;
        $data['contact_last_name'] = $data['contact_last_name'] ?? $user->last_name;
        $data['contact_email'] = $data['contact_email'] ?? $user->email;
        $data['preferred_language'] = $data['preferred_language'] ?? $user->preferred_language;

        return $this->createPublicLead($data);
    }

    public function getUserInquiries(int $userId, array $filters = []): LengthAwarePaginator
    {
        return Lead::query()
            ->where('user_id', $userId)
            ->with(['property.primaryImage', 'property.city', 'property.canton', 'property.translations'])
            ->orderByDesc('created_at')
            ->paginate($filters['limit'] ?? 20, ['*'], 'page', $filters['page'] ?? 1);
    }
}
