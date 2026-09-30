<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\AddOn;
use App\Models\Amenity;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\BookingAddOn;
use App\Models\Faq;
use App\Models\GalleryImage;
use App\Models\Package;
use App\Models\Payment;
use App\Models\PricingRule;
use App\Models\Setting;
use App\Models\User;

/*
| M1: every factory persists a valid record, and relationships resolve.
*/

it('persists a valid record for every model factory', function (string $model) {
    $record = $model::factory()->create();

    expect($record->exists)->toBeTrue()
        ->and($model::query()->count())->toBeGreaterThanOrEqual(1);
})->with([
    User::class,
    Package::class,
    AddOn::class,
    PricingRule::class,
    Booking::class,
    BookingAddOn::class,
    Payment::class,
    BlockedDate::class,
    Amenity::class,
    GalleryImage::class,
    Faq::class,
    Setting::class,
    ActivityLog::class,
]);

it('creates bookings with Philippine guest data and package-consistent amounts', function () {
    $booking = Booking::factory()->create();

    expect($booking->guest_phone)->toMatch('/^\+639\d{9}$/')
        ->and($booking->status)->toBe(BookingStatus::Pending)
        ->and($booking->total_amount_cents)->toBe($booking->package->base_price_cents)
        ->and($booking->downpayment_required_cents)->toBe(intdiv($booking->total_amount_cents, 2))
        ->and($booking->ends_at->greaterThan($booking->starts_at))->toBeTrue()
        ->and($booking->reference_code)->toStartWith('WP-');
});

it('resolves an overnight package window across midnight', function () {
    $package = Package::factory()->overnight()->create();

    $booking = Booking::factory()->forPackageOn($package, now()->addWeek())->create();

    expect($booking->starts_at->format('H:i'))->toBe('19:00')
        ->and($booking->ends_at->format('H:i'))->toBe('05:00')
        ->and($booking->ends_at->toDateString())->toBe($booking->starts_at->copy()->addDay()->toDateString());
});

it('wires booking relationships', function () {
    $booking = Booking::factory()->approved()->create();
    $addOn = AddOn::factory()->create();
    $booking->addOns()->attach($addOn, ['quantity' => 2, 'unit_price_cents' => $addOn->price_cents]);
    Payment::factory()->verified()->for($booking)->create();

    $booking->refresh();

    expect($booking->approver?->role)->toBe(UserRole::Owner)
        ->and($booking->addOns)->toHaveCount(1)
        ->and($booking->addOns->first()?->pivot->quantity)->toBe(2)
        ->and($booking->payments->first()?->status)->toBe(PaymentStatus::Verified)
        ->and($booking->package->bookings)->toHaveCount(1);
});

it('formats money accessors from centavos', function () {
    $booking = Booking::factory()->create(['total_amount_cents' => 900000, 'downpayment_required_cents' => 450000]);

    expect($booking->formatted_total)->toBe('₱9,000.00')
        ->and($booking->formatted_downpayment)->toBe('₱4,500.00');
});

it('soft deletes bookings', function () {
    $booking = Booking::factory()->create();

    $booking->delete();

    expect(Booking::query()->count())->toBe(0)
        ->and(Booking::withTrashed()->count())->toBe(1);
});

it('logs activity against a polymorphic subject', function () {
    $log = ActivityLog::factory()->create();

    expect($log->subject)->toBeInstanceOf(Booking::class)
        ->and($log->user?->isOwner())->toBeTrue()
        ->and($log->properties)->toBe(['from' => 'pending', 'to' => 'approved']);
});
