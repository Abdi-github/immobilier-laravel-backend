<?php

declare(strict_types=1);

namespace App\Domain\Lead\Enums;

enum LeadPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::LOW => __('priorities.low'),
            self::MEDIUM => __('priorities.medium'),
            self::HIGH => __('priorities.high'),
            self::URGENT => __('priorities.urgent'),
        };
    }
}
