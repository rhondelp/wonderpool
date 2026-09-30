<?php

namespace App\Enums;

/**
 * What a payment covers.
 */
enum PaymentType: string
{
    case Downpayment = 'downpayment';
    case Balance = 'balance';
    case Full = 'full';

    /**
     * Human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Downpayment => 'Downpayment',
            self::Balance => 'Balance',
            self::Full => 'Full payment',
        };
    }
}
