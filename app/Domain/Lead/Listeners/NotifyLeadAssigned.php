<?php

declare(strict_types=1);

namespace App\Domain\Lead\Listeners;

use App\Domain\Lead\Events\LeadAssigned;
use App\Domain\Notification\Jobs\SendEmailJob;
use App\Domain\Notification\Mail\NewLead;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyLeadAssigned implements ShouldQueue
{
    public string $queue = 'emails';

    public function handle(LeadAssigned $event): void
    {
        $lead = $event->lead;
        $lead->loadMissing(['assignedTo', 'property']);

        $agent = $lead->assignedTo;
        if (!$agent) {
            return;
        }

        SendEmailJob::dispatch(
            new NewLead(
                recipientEmail: $agent->email,
                lead: $lead,
                locale: $agent->preferred_language ?? 'en',
            ),
        );
    }
}
