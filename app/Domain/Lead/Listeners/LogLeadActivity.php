<?php

declare(strict_types=1);

namespace App\Domain\Lead\Listeners;

use App\Domain\Lead\Events\LeadAssigned;
use App\Domain\Lead\Events\LeadClosed;
use App\Domain\Lead\Events\LeadCreated;
use App\Domain\Lead\Events\LeadStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

final class LogLeadActivity implements ShouldQueue
{
    public string $queue = 'default';

    public function handle(LeadCreated|LeadAssigned|LeadStatusChanged|LeadClosed $event): void
    {
        $lead = $event->lead;

        $context = match (true) {
            $event instanceof LeadCreated => [
                'action' => 'created',
                'lead_id' => $lead->id,
                'property_id' => $lead->property_id,
            ],
            $event instanceof LeadAssigned => [
                'action' => 'assigned',
                'lead_id' => $lead->id,
                'assigned_to' => $lead->assigned_to,
                'assigned_by' => $event->assignedBy,
            ],
            $event instanceof LeadStatusChanged => [
                'action' => 'status_changed',
                'lead_id' => $lead->id,
                'from' => $event->fromStatus,
                'to' => $event->toStatus,
            ],
            $event instanceof LeadClosed => [
                'action' => 'closed',
                'lead_id' => $lead->id,
                'outcome' => $event->outcome,
            ],
        };

        Log::channel('daily')->info('Lead activity', $context);
    }
}
