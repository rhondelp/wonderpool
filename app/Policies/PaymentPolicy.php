<?php

namespace App\Policies;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;

/**
 * Payment permissions (D-029): any admin may view proofs and verify/reject pending payments;
 * voiding (rejecting) an already verified payment is owner only.
 */
class PaymentPolicy
{
    /**
     * View the uploaded proof file (private disk, D-032).
     */
    public function viewProof(User $user, Payment $payment): bool
    {
        return $user->is_active;
    }

    /**
     * Verify a pending payment.
     */
    public function verify(User $user, Payment $payment): bool
    {
        return $user->is_active;
    }

    /**
     * Reject a pending payment; void a verified one (owner only).
     */
    public function reject(User $user, Payment $payment): bool
    {
        return $payment->status === PaymentStatus::Verified ? $user->isOwner() : $user->is_active;
    }
}
