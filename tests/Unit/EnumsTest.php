<?php

use App\Enums\BookingStatus;
use App\Enums\GalleryCategory;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\PricingAdjustmentType;
use App\Enums\PricingRuleType;
use App\Enums\UserRole;

/*
| M1: every enum case has a label; colored enums map to x-ui.badge palette keys.
*/

it('labels every case', function (string $enum) {
    foreach ($enum::cases() as $case) {
        expect($case->label())->toBeString()->not->toBeEmpty();
    }
})->with([
    BookingStatus::class,
    PaymentStatus::class,
    PaymentType::class,
    UserRole::class,
    PricingRuleType::class,
    PricingAdjustmentType::class,
    GalleryCategory::class,
]);

it('maps colors to badge palette keys', function (string $enum) {
    foreach ($enum::cases() as $case) {
        expect($case->color())->toBeIn(['pool', 'garden', 'amber', 'rose', 'slate']);
    }
})->with([
    BookingStatus::class,
    PaymentStatus::class,
    UserRole::class,
]);

it('uses the PLAN.md booking statuses and colors', function () {
    expect(array_map(fn (BookingStatus $s) => $s->value, BookingStatus::cases()))
        ->toBe(['pending', 'approved', 'rejected', 'cancelled', 'completed'])
        ->and(BookingStatus::Approved->color())->toBe('garden')
        ->and(BookingStatus::Completed->color())->toBe('pool')
        ->and(BookingStatus::blocking())->toBe([BookingStatus::Pending, BookingStatus::Approved]);
});
