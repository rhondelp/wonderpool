<?php

namespace App\Services\Booking;

use App\Enums\ActivityAction;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Exceptions\Booking\InvalidPaymentProofException;
use App\Exceptions\Booking\PaymentProofNotAllowedException;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\ActivityLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Exceptions\DecoderException;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Guest payment proofs (PLAN.md §5.6, D-026). Files go to the PRIVATE disk only
 * (storage/app/private/payment-proofs/{booking_id}/{uuid}.{jpg|pdf}); there is no public URL.
 * Images are re-encoded to JPEG (orientation applied, EXIF/GPS and other metadata dropped,
 * longest edge ≤ MAX_EDGE). PDFs cannot be re-encoded with GD and are stored as uploaded.
 * A booking keeps one current proof: re-uploading replaces the pending downpayment's file.
 */
class PaymentProofService
{
    /** Private storage disk. */
    public const DISK = 'local';

    /** Folder on the private disk. */
    public const DIRECTORY = 'payment-proofs';

    /** Longest edge for re-encoded images, in pixels. */
    public const MAX_EDGE = 2000;

    /** JPEG quality for re-encoded images. */
    public const QUALITY = 85;

    /**
     * @param  ImageManager  $images  Intervention Image (GD)
     * @param  ActivityLogger  $logger  Audit trail
     */
    public function __construct(
        private readonly ImageManager $images,
        private readonly ActivityLogger $logger,
    ) {
    }

    /**
     * Stores (or replaces) the booking's payment proof.
     *
     * @param  Booking  $booking  Must be pending
     * @param  UploadedFile  $file  Validated jpg/png/webp/pdf ≤ 5 MB
     * @param  string|null  $referenceNo  GCash/bank transaction reference typed by the guest
     *
     * @throws PaymentProofNotAllowedException When the booking is not pending
     * @throws InvalidPaymentProofException When an image cannot be decoded
     */
    public function store(Booking $booking, UploadedFile $file, ?string $referenceNo = null): Payment
    {
        if ($booking->status !== BookingStatus::Pending) {
            throw new PaymentProofNotAllowedException("Payment proof can only be sent while the booking is pending (this booking is {$booking->status->label()}).");
        }

        $path = $this->write($booking, $file);

        try {
            return DB::transaction(function () use ($booking, $path, $referenceNo, $file): Payment {
                $payment = Payment::query()
                    ->where('booking_id', $booking->id)
                    ->where('type', PaymentType::Downpayment)
                    ->where('status', PaymentStatus::Pending)
                    ->latest('id')
                    ->first();

                $oldPath = $payment?->proof_path;

                if ($payment === null) {
                    $payment = Payment::query()->create([
                        'booking_id' => $booking->id,
                        'type' => PaymentType::Downpayment,
                        'amount_cents' => $booking->downpayment_required_cents,
                        'status' => PaymentStatus::Pending,
                        'proof_path' => $path,
                        'reference_no' => $referenceNo,
                    ]);
                } else {
                    $payment->update(['proof_path' => $path, 'reference_no' => $referenceNo ?? $payment->reference_no]);
                }

                $this->logger->log(ActivityAction::PaymentProofUploaded, $booking, [
                    'payment_id' => $payment->id,
                    'replaced' => $oldPath !== null,
                    'kind' => $this->isPdf($file) ? 'pdf' : 'image',
                ]);

                if ($oldPath !== null && $oldPath !== $path) {
                    DB::afterCommit(fn () => Storage::disk(self::DISK)->delete($oldPath));
                }

                return $payment;
            });
        } catch (Throwable $e) {
            Storage::disk(self::DISK)->delete($path);

            throw $e;
        }
    }

    /**
     * Writes the file to the private disk and returns its path.
     */
    private function write(Booking $booking, UploadedFile $file): string
    {
        $base = self::DIRECTORY.'/'.$booking->id.'/'.Str::uuid();

        if ($this->isPdf($file)) {
            Storage::disk(self::DISK)->putFileAs(dirname($base), $file, basename($base).'.pdf');

            return $base.'.pdf';
        }

        try {
            $encoded = $this->images->read($file->getRealPath())
                ->scaleDown(self::MAX_EDGE, self::MAX_EDGE)
                ->toJpeg(self::QUALITY);
        } catch (DecoderException) {
            throw new InvalidPaymentProofException('We could not read that image. Please upload a clear screenshot (JPG, PNG or WebP) or a PDF.');
        }

        Storage::disk(self::DISK)->put($base.'.jpg', (string) $encoded);

        return $base.'.jpg';
    }

    /**
     * Whether the upload is a PDF (by detected MIME type, not the client name).
     */
    private function isPdf(UploadedFile $file): bool
    {
        return $file->getMimeType() === 'application/pdf';
    }
}
