<?php

namespace App\Enums;

/**
 * Verification status of a payment / uploaded proof.
 */
enum PaymentStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';

    /**
     * Human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending verification',
            self::Verified => 'Verified',
            self::Rejected => 'Rejected',
        };
    }

    /**
     * Badge color key understood by x-ui.badge.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Verified => 'garden',
            self::Rejected => 'rose',
        };
    }
}
