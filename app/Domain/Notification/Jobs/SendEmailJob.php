<?php

declare(strict_types=1);

namespace App\Domain\Notification\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly Mailable $mailable,
    ) {
        $this->onQueue(config('immobilier.queues.emails', 'emails'));
    }

    public function handle(): void
    {
        Mail::send($this->mailable);

        Log::info('Email sent', [
            'mailable' => $this->mailable::class,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Email sending failed', [
            'mailable' => $this->mailable::class,
            'error' => $exception->getMessage(),
        ]);
    }
}
