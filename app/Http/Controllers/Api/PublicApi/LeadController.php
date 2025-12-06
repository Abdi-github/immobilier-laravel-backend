<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\PublicApi;

use App\Domain\Lead\Services\LeadService;
use App\Domain\Shared\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Resources\LeadResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LeadController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly LeadService $leadService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'property_id' => 'required|integer|exists:properties,id',
            'contact_first_name' => 'required|string|max:100',
            'contact_last_name' => 'required|string|max:100',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'message' => 'required|string|max:5000',
            'inquiry_type' => 'required|string|in:general_inquiry,viewing_request,price_inquiry,availability_check,documentation_request,other',
            'preferred_contact_method' => 'nullable|string|in:email,phone,sms',
            'preferred_language' => 'nullable|string|in:en,fr,de,it',
            'source' => 'nullable|string|in:website,mobile_app,email,phone,walk_in,referral,social_media,other',
        ]);

        try {
            $lead = $this->leadService->createPublicLead($validated);
            $lead->load(['property.primaryImage']);

            return $this->createdResponse(
                new LeadResource($lead),
                __('leads.created'),
            );
        } catch (DomainException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function storeAuthenticated(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'property_id' => 'required|integer|exists:properties,id',
            'contact_first_name' => 'nullable|string|max:100',
            'contact_last_name' => 'nullable|string|max:100',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'message' => 'required|string|max:5000',
            'inquiry_type' => 'required|string|in:general_inquiry,viewing_request,price_inquiry,availability_check,documentation_request,other',
            'preferred_contact_method' => 'nullable|string|in:email,phone,sms',
            'preferred_language' => 'nullable|string|in:en,fr,de,it',
        ]);

        try {
            $lead = $this->leadService->createAuthenticatedLead($validated, $request->user());
            $lead->load(['property.primaryImage']);

            return $this->createdResponse(
                new LeadResource($lead),
                __('common.lead_created'),
            );
        } catch (DomainException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
    }

    public function myInquiries(Request $request): JsonResponse
    {
        $filters = $request->only(['page', 'limit']);
        $leads = $this->leadService->getUserInquiries($request->user()->id, $filters);

        return $this->paginatedResponse(
            $leads->through(fn ($l) => new LeadResource($l)),
            __('common.retrieved'),
        );
    }
}
