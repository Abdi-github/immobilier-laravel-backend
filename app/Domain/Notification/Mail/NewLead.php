<?php

declare(strict_types=1);

namespace App\Domain\Notification\Mail;

use App\Domain\Lead\Models\Lead;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class NewLead extends Mailable
{

    public function __construct(
        public readonly string $recipientEmail,
        public readonly Lead $lead,
        string $locale,
    ) {
        $this->locale($locale);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [$this->recipientEmail],
            subject: __('emails.lead_subject', [], $this->locale),
        );
    }

    public function content(): Content
    {
        $leadUrl = config('immobilier.admin_url') . '/leads/' . $this->lead->id;

        return new Content(
            view: 'emails.new-lead',
            with: [
                'locale' => $this->locale,
                'lead' => $this->lead,
                'leadUrl' => $leadUrl,
            ],
        );
    }
}
