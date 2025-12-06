<?php

declare(strict_types=1);

namespace App\Domain\Notification\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class PasswordReset extends Mailable
{

    public function __construct(
        public readonly string $recipientEmail,
        public readonly string $firstName,
        public readonly string $resetToken,
        string $locale,
    ) {
        $this->locale($locale);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [$this->recipientEmail],
            subject: __('emails.reset_subject', [], $this->locale),
        );
    }

    public function content(): Content
    {
        $resetUrl = config('immobilier.frontend_url') . '/reset-password?token=' . $this->resetToken;

        return new Content(
            view: 'emails.password-reset',
            with: [
                'locale' => $this->locale,
                'firstName' => $this->firstName,
                'resetUrl' => $resetUrl,
                'expiryMinutes' => config('immobilier.auth.password_reset_token_expiry') * 60,
            ],
        );
    }
}
