<?php

declare(strict_types=1);

namespace App\Domain\Notification\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class VerifyEmail extends Mailable
{

    public function __construct(
        public readonly string $recipientEmail,
        public readonly string $firstName,
        public readonly string $verificationToken,
        string $locale,
    ) {
        $this->locale($locale);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [$this->recipientEmail],
            subject: __('emails.verify_subject', [], $this->locale),
        );
    }

    public function content(): Content
    {
        $verificationUrl = config('immobilier.frontend_url') . '/verify-email?token=' . $this->verificationToken;

        return new Content(
            view: 'emails.verify',
            with: [
                'locale' => $this->locale,
                'firstName' => $this->firstName,
                'verificationUrl' => $verificationUrl,
                'expiryHours' => config('immobilier.auth.verification_token_expiry'),
            ],
        );
    }
}
