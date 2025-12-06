<?php

declare(strict_types=1);

namespace App\Domain\Property\Observers;

use App\Domain\Property\Models\Property;
use Illuminate\Support\Str;

class PropertyObserver
{
    public function creating(Property $property): void
    {
        if (empty($property->external_id)) {
            $property->external_id = (string) Str::uuid();
        }
    }
}
