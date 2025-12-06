<?php

declare(strict_types=1);

namespace App\Domain\Notification\Mail;

use App\Domain\Property\Models\Property;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class PropertySubmitted extends Mailable
{

    public function __construct(
        public readonly string $recipientEmail,
        public readonly Property $property,
        string $locale,
    ) {
        $this->locale($locale);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [$this->recipientEmail],
            subject: __('emails.submitted_subject', [], $this->locale),
        );
    }

    public function content(): Content
    {
        $reviewUrl = config('immobilier.admin_url') . '/properties/' . $this->property->id;

        return new Content(
            view: 'emails.property-submitted',
            with: [
                'locale' => $this->locale,
                'property' => $this->property,
                'reviewUrl' => $reviewUrl,
            ],
        );
    }
}
