<?php

declare(strict_types=1);

namespace App\Domain\User\Enums;

enum AlertFrequency: string
{
    case INSTANT = 'instant';
    case DAILY = 'daily';
    case WEEKLY = 'weekly';

    public function label(): string
    {
        return match ($this) {
            self::INSTANT => __('frequencies.instant'),
            self::DAILY => __('frequencies.daily'),
            self::WEEKLY => __('frequencies.weekly'),
        };
    }
}
