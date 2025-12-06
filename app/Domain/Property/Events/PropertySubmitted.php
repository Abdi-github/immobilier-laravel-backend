<?php

declare(strict_types=1);

namespace App\Domain\Property\Events;

use App\Domain\Property\Models\Property;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PropertySubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Property $property,
    ) {}
}
