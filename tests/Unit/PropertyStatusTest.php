<?php

declare(strict_types=1);

use App\Domain\Property\Enums\PropertyStatus;

describe('PropertyStatus transitions', function () {

    it('allows DRAFT → PENDING_APPROVAL', function () {
        expect(PropertyStatus::DRAFT->canTransitionTo(PropertyStatus::PENDING_APPROVAL))->toBeTrue();
    });

    it('allows DRAFT → ARCHIVED', function () {
        expect(PropertyStatus::DRAFT->canTransitionTo(PropertyStatus::ARCHIVED))->toBeTrue();
    });

    it('disallows DRAFT → PUBLISHED', function () {
        expect(PropertyStatus::DRAFT->canTransitionTo(PropertyStatus::PUBLISHED))->toBeFalse();
    });

    it('disallows DRAFT → APPROVED', function () {
        expect(PropertyStatus::DRAFT->canTransitionTo(PropertyStatus::APPROVED))->toBeFalse();
    });

    it('allows PENDING_APPROVAL → APPROVED', function () {
        expect(PropertyStatus::PENDING_APPROVAL->canTransitionTo(PropertyStatus::APPROVED))->toBeTrue();
    });

    it('allows PENDING_APPROVAL → REJECTED', function () {
        expect(PropertyStatus::PENDING_APPROVAL->canTransitionTo(PropertyStatus::REJECTED))->toBeTrue();
    });

    it('allows PENDING_APPROVAL → DRAFT (return to draft)', function () {
        expect(PropertyStatus::PENDING_APPROVAL->canTransitionTo(PropertyStatus::DRAFT))->toBeTrue();
    });

    it('allows APPROVED → PUBLISHED', function () {
        expect(PropertyStatus::APPROVED->canTransitionTo(PropertyStatus::PUBLISHED))->toBeTrue();
    });

    it('disallows APPROVED → DRAFT', function () {
        expect(PropertyStatus::APPROVED->canTransitionTo(PropertyStatus::DRAFT))->toBeFalse();
    });

    it('allows REJECTED → DRAFT', function () {
        expect(PropertyStatus::REJECTED->canTransitionTo(PropertyStatus::DRAFT))->toBeTrue();
    });

    it('disallows REJECTED → PUBLISHED', function () {
        expect(PropertyStatus::REJECTED->canTransitionTo(PropertyStatus::PUBLISHED))->toBeFalse();
    });

    it('allows PUBLISHED → ARCHIVED', function () {
        expect(PropertyStatus::PUBLISHED->canTransitionTo(PropertyStatus::ARCHIVED))->toBeTrue();
    });

    it('disallows PUBLISHED → DRAFT', function () {
        expect(PropertyStatus::PUBLISHED->canTransitionTo(PropertyStatus::DRAFT))->toBeFalse();
    });

    it('allows ARCHIVED → DRAFT', function () {
        expect(PropertyStatus::ARCHIVED->canTransitionTo(PropertyStatus::DRAFT))->toBeTrue();
    });

    it('disallows ARCHIVED → PUBLISHED', function () {
        expect(PropertyStatus::ARCHIVED->canTransitionTo(PropertyStatus::PUBLISHED))->toBeFalse();
    });
});

describe('PropertyStatus labels', function () {
    it('returns a label for each status', function () {
        foreach (PropertyStatus::cases() as $status) {
            expect($status->label())->toBeString()->not->toBeEmpty();
        }
    });
});
