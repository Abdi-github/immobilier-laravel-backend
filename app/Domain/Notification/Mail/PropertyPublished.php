<?php

declare(strict_types=1);

namespace App\Domain\Notification\Mail;

use App\Domain\Property\Models\Property;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class PropertyPublished extends Mailable
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
            subject: __('emails.published_subject', [], $this->locale),
        );
    }

    public function content(): Content
    {
        $listingUrl = config('immobilier.frontend_url') . '/properties/' . $this->property->slug;

        return new Content(
            view: 'emails.property-published',
            with: [
                'locale' => $this->locale,
                'property' => $this->property,
                'listingUrl' => $listingUrl,
            ],
        );
    }
}
