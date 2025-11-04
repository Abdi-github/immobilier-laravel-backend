<?php

declare(strict_types=1);

namespace App\Domain\User\Enums;

enum AccountStatus: string
{
    case ACTIVE = 'active';
    case PENDING = 'pending';
    case SUSPENDED = 'suspended';
    case INACTIVE = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => __('statuses.active'),
            self::PENDING => __('statuses.pending'),
            self::SUSPENDED => __('statuses.suspended'),
            self::INACTIVE => __('statuses.inactive'),
        };
    }
}
