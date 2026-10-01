<?php

namespace App\Services\Booking;

use App\Support\Money;

/**
 * Immutable result of PricingService::quote(). All amounts are integer centavos (D-001).
 * The same object feeds the live quote (M5) and the booking snapshot, so they always match.
 */
final readonly class PriceBreakdown
{
    /**
     * @param  int  $packageId  Quoted package
     * @param  string  $date  Booking start date (Y-m-d) used for rule matching
     * @param  int  $guestCount  Guests quoted
     * @param  int  $basePriceCents  Package base price
     * @param  list<array{rule_id: int, label: string, delta_cents: int, running_cents: int}>  $adjustments  Applied pricing rules in order
     * @param  int  $packageSubtotalCents  Base after all rules (never below 0)
     * @param  list<array{add_on_id: int, name: string, quantity: int, unit_price_cents: int, line_total_cents: int}>  $addOns  Selected add-ons
     * @param  int  $addOnsTotalCents  Sum of add-on lines
     * @param  int  $totalCents  Package subtotal + add-ons
     * @param  int  $downpaymentPercent  Setting booking.downpayment_percent at quote time
     * @param  int  $downpaymentRequiredCents  Rounded half up to the centavo
     */
    public function __construct(
        public int $packageId,
        public string $date,
        public int $guestCount,
        public int $basePriceCents,
        public array $adjustments,
        public int $packageSubtotalCents,
        public array $addOns,
        public int $addOnsTotalCents,
        public int $totalCents,
        public int $downpaymentPercent,
        public int $downpaymentRequiredCents,
    ) {
    }

    /**
     * Array form for JSON responses, with peso-formatted strings alongside centavos.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'package_id' => $this->packageId,
            'date' => $this->date,
            'guest_count' => $this->guestCount,
            'base_price_cents' => $this->basePriceCents,
            'adjustments' => array_map(fn (array $a): array => $a + ['delta_formatted' => Money::format($a['delta_cents'])], $this->adjustments),
            'package_subtotal_cents' => $this->packageSubtotalCents,
            'add_ons' => array_map(fn (array $a): array => $a + ['line_total_formatted' => Money::format($a['line_total_cents'])], $this->addOns),
            'add_ons_total_cents' => $this->addOnsTotalCents,
            'total_cents' => $this->totalCents,
            'total_formatted' => Money::format($this->totalCents),
            'downpayment_percent' => $this->downpaymentPercent,
            'downpayment_required_cents' => $this->downpaymentRequiredCents,
            'downpayment_formatted' => Money::format($this->downpaymentRequiredCents),
        ];
    }
}
