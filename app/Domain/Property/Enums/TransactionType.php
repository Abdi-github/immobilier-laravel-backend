<?php

declare(strict_types=1);

namespace App\Domain\Property\Enums;

enum TransactionType: string
{
    case RENT = 'rent';
    case BUY = 'buy';

    public function label(): string
    {
        return match ($this) {
            self::RENT => __('transaction_types.rent'),
            self::BUY => __('transaction_types.buy'),
        };
    }
}
