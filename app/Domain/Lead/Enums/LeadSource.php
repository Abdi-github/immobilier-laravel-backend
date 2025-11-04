<?php

declare(strict_types=1);

namespace App\Domain\Lead\Enums;

enum LeadSource: string
{
    case WEBSITE = 'website';
    case MOBILE_APP = 'mobile_app';
    case EMAIL = 'email';
    case PHONE = 'phone';
    case WALK_IN = 'walk_in';
    case REFERRAL = 'referral';
    case SOCIAL_MEDIA = 'social_media';
    case OTHER = 'other';
}
