<?php

declare(strict_types=1);

namespace App\Domain\Lead\Events;

use App\Domain\Lead\Models\Lead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class LeadClosed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Lead $lead,
        public readonly string $outcome,
    ) {}
}
