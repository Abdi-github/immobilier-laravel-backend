<?php

declare(strict_types=1);

namespace App\Domain\Lead\Listeners;

use App\Domain\Lead\Events\LeadCreated;
use App\Domain\Notification\Jobs\SendEmailJob;
use App\Domain\Notification\Mail\NewLead;
use Illuminate\Contracts\Queue\ShouldQueue;

final class NotifyNewLead implements ShouldQueue
{
    public string $queue = 'emails';

    public function handle(LeadCreated $event): void
    {
        $lead = $event->lead;
        $lead->loadMissing(['property', 'agency']);

        // Notify agency admin(s)
        if ($lead->agency) {
            $agencyAdmins = $lead->agency->users()
                ->where('user_type', 'agency_admin')
                ->get();

            foreach ($agencyAdmins as $admin) {
                SendEmailJob::dispatch(
                    new NewLead(
                        recipientEmail: $admin->email,
                        lead: $lead,
                        locale: $admin->preferred_language ?? 'en',
                    ),
                );
            }
        }

        // Notify assigned agent if already assigned
        if ($lead->assigned_to) {
            $lead->loadMissing('assignedTo');
            $agent = $lead->assignedTo;
            if ($agent) {
                SendEmailJob::dispatch(
                    new NewLead(
                        recipientEmail: $agent->email,
                        lead: $lead,
                        locale: $agent->preferred_language ?? 'en',
                    ),
                );
            }
        }
    }
}
