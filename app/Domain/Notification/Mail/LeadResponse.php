<?php

declare(strict_types=1);

namespace App\Domain\Notification\Mail;

use App\Domain\Lead\Models\Lead;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class LeadResponse extends Mailable
{

    public function __construct(
        public readonly string $recipientEmail,
        public readonly string $recipientName,
        public readonly Lead $lead,
        string $locale,
    ) {
        $this->locale($locale);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [$this->recipientEmail],
            subject: __('emails.response_subject', [], $this->locale),
        );
    }

    public function content(): Content
    {
        $responseUrl = config('immobilier.frontend_url') . '/inquiries/' . $this->lead->id;

        return new Content(
            view: 'emails.lead-response',
            with: [
                'locale' => $this->locale,
                'recipientName' => $this->recipientName,
                'lead' => $this->lead,
                'responseUrl' => $responseUrl,
            ],
        );
    }
}
