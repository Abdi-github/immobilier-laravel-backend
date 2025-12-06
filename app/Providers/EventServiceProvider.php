<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Event-to-listener mappings.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Auth events
        \App\Domain\Auth\Events\UserRegistered::class => [
            \App\Domain\Auth\Listeners\SendVerificationEmail::class,
        ],
        \App\Domain\Auth\Events\UserEmailVerified::class => [
            \App\Domain\Auth\Listeners\SendWelcomeEmail::class,
        ],
        \App\Domain\Auth\Events\PasswordResetRequested::class => [
            \App\Domain\Auth\Listeners\SendPasswordResetEmail::class,
        ],
        \App\Domain\Auth\Events\PasswordChanged::class => [
            \App\Domain\Auth\Listeners\SendPasswordChangedEmail::class,
        ],

        // Property events
        \App\Domain\Property\Events\PropertyCreated::class => [
            \App\Domain\Property\Listeners\IndexPropertyInSearch::class,
        ],
        \App\Domain\Property\Events\PropertySubmitted::class => [
            \App\Domain\Property\Listeners\NotifyPropertySubmitted::class,
        ],
        \App\Domain\Property\Events\PropertyApproved::class => [
            \App\Domain\Property\Listeners\NotifyPropertyApproved::class,
            \App\Domain\Property\Listeners\IndexPropertyInSearch::class,
        ],
        \App\Domain\Property\Events\PropertyRejected::class => [
            \App\Domain\Property\Listeners\NotifyPropertyRejected::class,
        ],
        \App\Domain\Property\Events\PropertyPublished::class => [
            \App\Domain\Property\Listeners\NotifyPropertyPublished::class,
            \App\Domain\Property\Listeners\IndexPropertyInSearch::class,
            \App\Domain\Property\Listeners\RequestPropertyTranslations::class,
        ],
        \App\Domain\Property\Events\PropertyArchived::class => [
            \App\Domain\Property\Listeners\RemovePropertyFromSearch::class,
        ],

        // Lead events
        \App\Domain\Lead\Events\LeadCreated::class => [
            \App\Domain\Lead\Listeners\NotifyNewLead::class,
            \App\Domain\Lead\Listeners\LogLeadActivity::class,
        ],
        \App\Domain\Lead\Events\LeadAssigned::class => [
            \App\Domain\Lead\Listeners\NotifyLeadAssigned::class,
            \App\Domain\Lead\Listeners\LogLeadActivity::class,
        ],
        \App\Domain\Lead\Events\LeadStatusChanged::class => [
            \App\Domain\Lead\Listeners\LogLeadActivity::class,
        ],
        \App\Domain\Lead\Events\LeadClosed::class => [
            \App\Domain\Lead\Listeners\LogLeadActivity::class,
        ],

        // Agency events
        \App\Domain\Agency\Events\AgencyVerified::class => [
            \App\Domain\Agency\Listeners\UpdateAgencyPropertyCount::class,
        ],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
