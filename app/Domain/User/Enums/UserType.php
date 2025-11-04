<?php

declare(strict_types=1);

namespace App\Domain\User\Enums;

enum UserType: string
{
    case END_USER = 'end_user';
    case OWNER = 'owner';
    case AGENT = 'agent';
    case AGENCY_ADMIN = 'agency_admin';
    case PLATFORM_ADMIN = 'platform_admin';
    case SUPER_ADMIN = 'super_admin';

    public function label(): string
    {
        return match ($this) {
            self::END_USER => __('user_types.end_user'),
            self::OWNER => __('user_types.owner'),
            self::AGENT => __('user_types.agent'),
            self::AGENCY_ADMIN => __('user_types.agency_admin'),
            self::PLATFORM_ADMIN => __('user_types.platform_admin'),
            self::SUPER_ADMIN => __('user_types.super_admin'),
        };
    }

    public function isProfessional(): bool
    {
        return in_array($this, [self::AGENT, self::AGENCY_ADMIN]);
    }

    public function isAdmin(): bool
    {
        return in_array($this, [self::PLATFORM_ADMIN, self::SUPER_ADMIN]);
    }
}
