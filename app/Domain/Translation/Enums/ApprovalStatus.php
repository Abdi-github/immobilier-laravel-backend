<?php

declare(strict_types=1);

namespace App\Domain\Translation\Enums;

enum ApprovalStatus: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
}
