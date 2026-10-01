<?php

namespace App\Enums;

/**
 * Payment situation of a booking, derived from its payments (not stored). Precedence:
 * paid (verified ≥ total) → proof_pending (a pending proof awaits review) → partial (some verified) → unpaid.
 * Computed by PaymentService::summary() and filtered in SQL by Booking::scopePaymentState() (D-030).
 */
enum PaymentState: string
{
    case Unpaid = 'unpaid';
    case ProofPending = 'proof_pending';
    case Partial = 'partial';
    case Paid = 'paid';

    /**
     * Human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::ProofPending => 'Proof to review',
            self::Partial => 'Partially paid',
            self::Paid => 'Paid in full',
        };
    }

    /**
     * Badge color key understood by x-ui.badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::Unpaid => 'slate',
            self::ProofPending => 'amber',
            self::Partial => 'pool',
            self::Paid => 'garden',
        };
    }
}
