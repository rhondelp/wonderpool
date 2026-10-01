<?php

namespace App\Services\Booking;

use App\Enums\ActivityAction;
use App\Enums\PaymentState;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Exceptions\Booking\PaymentActionNotAllowedException;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;

/**
 * Admin payment handling (D-030): record manual payments (verified immediately), verify or
 * reject uploaded proofs, and summarize what a booking has paid. Only verified payments count.
 * Every action is logged with the booking as subject so it shows in the booking timeline.
 */
class PaymentService
{
    /**
     * @param  ActivityLogger  $logger  Audit trail
     */
    public function __construct(private readonly ActivityLogger $logger)
    {
    }

    /**
     * Records a payment received outside the website (cash, GCash, bank) as verified.
     *
     * @param  int  $amountCents  Amount received, > 0
     * @param  User  $actor  Admin recording it
     * @param  string|null  $referenceNo  Transaction reference
     * @param  string|null  $notes  Internal note
     *
     * @throws PaymentActionNotAllowedException When the amount is not positive
     */
    public function record(Booking $booking, PaymentType $type, int $amountCents, User $actor, ?string $referenceNo = null, ?string $notes = null): Payment
    {
        if ($amountCents <= 0) {
            throw new PaymentActionNotAllowedException('The amount must be greater than zero.');
        }

        return DB::transaction(function () use ($booking, $type, $amountCents, $actor, $referenceNo, $notes): Payment {
            $payment = Payment::query()->create([
                'booking_id' => $booking->id,
                'type' => $type,
                'amount_cents' => $amountCents,
                'status' => PaymentStatus::Verified,
                'reference_no' => $referenceNo,
                'notes' => $notes,
                'recorded_by' => $actor->id,
                'verified_by' => $actor->id,
                'verified_at' => now(),
            ]);

            $this->logger->log(ActivityAction::PaymentRecorded, $booking, [
                'payment_id' => $payment->id,
                'type' => $type->value,
                'amount_cents' => $amountCents,
            ], $actor);

            return $payment;
        });
    }

    /**
     * Marks a pending payment (usually an uploaded proof) as verified.
     *
     * @throws PaymentActionNotAllowedException When the payment is not pending
     */
    public function verify(Payment $payment, User $actor): Payment
    {
        if ($payment->status !== PaymentStatus::Pending) {
            throw new PaymentActionNotAllowedException("This payment is already {$payment->status->label()}.");
        }

        $payment->forceFill(['status' => PaymentStatus::Verified, 'verified_by' => $actor->id, 'verified_at' => now(), 'rejection_reason' => null])->save();

        $this->logger->log(ActivityAction::PaymentVerified, $payment->booking, [
            'payment_id' => $payment->id,
            'amount_cents' => $payment->amount_cents,
        ], $actor);

        return $payment;
    }

    /**
     * Rejects a pending payment, or voids a verified one (owner only).
     *
     * @param  string  $reason  Shown to the guest on the tracking page
     *
     * @throws PaymentActionNotAllowedException When already rejected, the reason is empty, or a non-owner voids a verified payment
     */
    public function reject(Payment $payment, User $actor, string $reason): Payment
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new PaymentActionNotAllowedException('A reason is required to reject a payment.');
        }

        if ($payment->status === PaymentStatus::Rejected) {
            throw new PaymentActionNotAllowedException('This payment is already rejected.');
        }

        if ($payment->status === PaymentStatus::Verified && ! $actor->isOwner()) {
            throw new PaymentActionNotAllowedException('Only the owner can void a verified payment.');
        }

        $from = $payment->status;
        $payment->forceFill(['status' => PaymentStatus::Rejected, 'verified_by' => $actor->id, 'verified_at' => now(), 'rejection_reason' => $reason])->save();

        $this->logger->log(ActivityAction::PaymentRejected, $payment->booking, [
            'payment_id' => $payment->id,
            'from' => $from->value,
            'reason' => $reason,
        ], $actor);

        return $payment;
    }

    /**
     * Verifies every pending payment that has a proof (used when approving a booking).
     *
     * @return int Number of payments verified
     */
    public function verifyPendingProofs(Booking $booking, User $actor): int
    {
        $pending = $booking->payments()->where('status', PaymentStatus::Pending)->whereNotNull('proof_path')->get();

        foreach ($pending as $payment) {
            $this->verify($payment, $actor);
        }

        return $pending->count();
    }

    /**
     * What the booking has paid. Only verified payments count toward totals.
     *
     * @return array{total: int, verified: int, pending: int, balance_due: int, downpayment_required: int, downpayment_covered: bool, state: PaymentState}
     */
    public function summary(Booking $booking): array
    {
        // Reuses eager-loaded payments (index pages) to avoid one query per row.
        $payments = $booking->relationLoaded('payments') ? $booking->payments : $booking->payments()->get(['amount_cents', 'status', 'proof_path']);
        $verified = (int) $payments->where('status', PaymentStatus::Verified)->sum('amount_cents');
        $pendingPayments = $payments->where('status', PaymentStatus::Pending);
        $total = $booking->total_amount_cents;

        $state = match (true) {
            $verified >= $total => PaymentState::Paid,
            $pendingPayments->whereNotNull('proof_path')->isNotEmpty() => PaymentState::ProofPending,
            $verified > 0 => PaymentState::Partial,
            default => PaymentState::Unpaid,
        };

        return [
            'total' => $total,
            'verified' => $verified,
            'pending' => (int) $pendingPayments->sum('amount_cents'),
            'balance_due' => max(0, $total - $verified),
            'downpayment_required' => $booking->downpayment_required_cents,
            'downpayment_covered' => $verified >= $booking->downpayment_required_cents,
            'state' => $state,
        ];
    }
}
