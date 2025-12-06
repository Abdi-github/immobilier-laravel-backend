<?php

declare(strict_types=1);

namespace App\Domain\Agency\Observers;

use App\Domain\Agency\Models\Agency;

class AgencyObserver
{
    public function creating(Agency $agency): void
    {
        if ($agency->total_properties === null) {
            $agency->total_properties = 0;
        }
    }
}
