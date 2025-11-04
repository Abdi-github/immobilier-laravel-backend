<?php

declare(strict_types=1);

namespace App\Domain\Lead\Enums;

enum InquiryType: string
{
    case GENERAL_INQUIRY = 'general_inquiry';
    case VIEWING_REQUEST = 'viewing_request';
    case PRICE_INQUIRY = 'price_inquiry';
    case AVAILABILITY_CHECK = 'availability_check';
    case DOCUMENTATION_REQUEST = 'documentation_request';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::GENERAL_INQUIRY => __('inquiry_types.general_inquiry'),
            self::VIEWING_REQUEST => __('inquiry_types.viewing_request'),
            self::PRICE_INQUIRY => __('inquiry_types.price_inquiry'),
            self::AVAILABILITY_CHECK => __('inquiry_types.availability_check'),
            self::DOCUMENTATION_REQUEST => __('inquiry_types.documentation_request'),
            self::OTHER => __('inquiry_types.other'),
        };
    }
}
