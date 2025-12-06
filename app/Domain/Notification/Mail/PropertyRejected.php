<?php

declare(strict_types=1);

namespace App\Domain\Notification\Mail;

use App\Domain\Property\Models\Property;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class PropertyRejected extends Mailable
{

    public function __construct(
        public readonly string $recipientEmail,
        public readonly Property $property,
        public readonly string $reason,
        string $locale,
    ) {
        $this->locale($locale);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [$this->recipientEmail],
            subject: __('emails.rejected_subject', [], $this->locale),
        );
    }

    public function content(): Content
    {
        $editUrl = config('immobilier.admin_url') . '/properties/' . $this->property->id . '/edit';

        return new Content(
            view: 'emails.property-rejected',
            with: [
                'locale' => $this->locale,
                'property' => $this->property,
                'reason' => $this->reason,
                'editUrl' => $editUrl,
            ],
        );
    }
}
