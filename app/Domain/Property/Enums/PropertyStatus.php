<?php

declare(strict_types=1);

namespace App\Domain\Property\Enums;

enum PropertyStatus: string
{
    case DRAFT = 'DRAFT';
    case PENDING_APPROVAL = 'PENDING_APPROVAL';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
    case PUBLISHED = 'PUBLISHED';
    case ARCHIVED = 'ARCHIVED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => __('statuses.draft'),
            self::PENDING_APPROVAL => __('statuses.pending_approval'),
            self::APPROVED => __('statuses.approved'),
            self::REJECTED => __('statuses.rejected'),
            self::PUBLISHED => __('statuses.published'),
            self::ARCHIVED => __('statuses.archived'),
        };
    }

    /**
     * Valid transitions from each status.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::PENDING_APPROVAL, self::ARCHIVED],
            self::PENDING_APPROVAL => [self::APPROVED, self::REJECTED, self::DRAFT],
            self::APPROVED => [self::PUBLISHED, self::ARCHIVED],
            self::REJECTED => [self::DRAFT, self::ARCHIVED],
            self::PUBLISHED => [self::ARCHIVED],
            self::ARCHIVED => [self::DRAFT],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
