<?php

declare(strict_types=1);

namespace App\Domain\Lead\Enums;

enum LeadStatus: string
{
    case NEW = 'NEW';
    case CONTACTED = 'CONTACTED';
    case QUALIFIED = 'QUALIFIED';
    case VIEWING_SCHEDULED = 'VIEWING_SCHEDULED';
    case NEGOTIATING = 'NEGOTIATING';
    case WON = 'WON';
    case LOST = 'LOST';
    case ARCHIVED = 'ARCHIVED';

    public function label(): string
    {
        return match ($this) {
            self::NEW => __('lead_statuses.new'),
            self::CONTACTED => __('lead_statuses.contacted'),
            self::QUALIFIED => __('lead_statuses.qualified'),
            self::VIEWING_SCHEDULED => __('lead_statuses.viewing_scheduled'),
            self::NEGOTIATING => __('lead_statuses.negotiating'),
            self::WON => __('lead_statuses.won'),
            self::LOST => __('lead_statuses.lost'),
            self::ARCHIVED => __('lead_statuses.archived'),
        };
    }
}
