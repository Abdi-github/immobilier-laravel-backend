<?php

declare(strict_types=1);

namespace App\Domain\Notification\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class WelcomeEmail extends Mailable
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
            subject: __('emails.welcome_subject', [], $this->locale),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
            with: [
                'locale' => $this->locale,
                'firstName' => $this->firstName,
                'dashboardUrl' => config('immobilier.frontend_url') . '/dashboard',
            ],
        );
    }
}
