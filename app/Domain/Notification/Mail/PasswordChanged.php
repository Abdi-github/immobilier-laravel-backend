<?php

declare(strict_types=1);

namespace App\Domain\Notification\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class PasswordChanged extends Mailable
{

    public function __construct(
        public readonly string $recipientEmail,
        public readonly string $firstName,
        string $locale,
    ) {
        $this->locale($locale);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [$this->recipientEmail],
            subject: __('emails.changed_subject', [], $this->locale),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-changed',
            with: [
                'locale' => $this->locale,
                'firstName' => $this->firstName,
                'changedAt' => now()->format('Y-m-d H:i:s T'),
            ],
        );
    }
}
