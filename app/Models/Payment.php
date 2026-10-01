<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Support\Money;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Payment against a booking, optionally with an uploaded proof (private disk path).
 *
 * @property int $id
 * @property int $booking_id
 * @property PaymentType $type
 * @property int $amount_cents
 * @property string|null $proof_path
 * @property PaymentStatus $status
 * @property string|null $reference_no GCash/bank transaction reference
 * @property int|null $verified_by
 * @property Carbon|null $verified_at
 * @property string|null $notes Admin note when recording/handling the payment
 * @property int|null $recorded_by Admin who recorded a manual payment
 * @property string|null $rejection_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $formatted_amount
 */
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'booking_id',
        'type',
        'amount_cents',
        'proof_path',
        'status',
        'reference_no',
        'verified_by',
        'verified_at',
        'notes',
        'recorded_by',
        'rejection_reason',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PaymentType::class,
            'amount_cents' => 'integer',
            'status' => PaymentStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Admin who recorded a manual payment.
     *
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Whether a proof file was uploaded for this payment.
     */
    public function hasProof(): bool
    {
        return $this->proof_path !== null && $this->proof_path !== '';
    }

    /**
     * Whether the proof is a PDF (else an image).
     */
    public function proofIsPdf(): bool
    {
        return str_ends_with((string) $this->proof_path, '.pdf');
    }

    /**
     * Admin who verified or rejected the payment.
     *
     * @return BelongsTo<User, $this>
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Amount formatted as pesos.
     *
     * @return Attribute<string, never>
     */
    protected function formattedAmount(): Attribute
    {
        return Attribute::get(fn (): string => Money::format($this->amount_cents));
    }

    /**
     * Payments counted toward the booking balance.
     *
     * @param  Builder<Payment>  $query
     */
    public function scopeVerified(Builder $query): void
    {
        $query->where('status', PaymentStatus::Verified);
    }
}
