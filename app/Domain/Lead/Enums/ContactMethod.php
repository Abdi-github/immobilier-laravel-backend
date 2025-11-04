<?php

declare(strict_types=1);

namespace App\Domain\Lead\Enums;

enum ContactMethod: string
{
    case EMAIL = 'email';
    case PHONE = 'phone';
    case BOTH = 'both';
}
