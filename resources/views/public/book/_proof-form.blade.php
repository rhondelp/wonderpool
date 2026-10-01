{{--
    Payment proof upload form (booking payment step and tracking page). Posts to book.payment.store;
    files go to the private disk via PaymentProofService (D-026).

    @param \App\Models\Booking $booking
    @param string|null $return  "track" to come back to the tracking page after upload
    @param string $submitLabel
--}}
<form method="POST" action="{{ route('book.payment.store', $booking) }}" enctype="multipart/form-data" class="space-y-4">
    @csrf
    @if (($return ?? null) === 'track')
        <input type="hidden" name="return" value="track">
    @endif

    <div>
        <label for="proof" class="mb-1 block text-sm font-medium text-slate-700">Screenshot or PDF of your payment <span class="text-rose-600" aria-hidden="true">*</span></label>
        <input type="file" id="proof" name="proof" accept="image/jpeg,image/png,image/webp,application/pdf" required
               @error('proof') aria-invalid="true" aria-describedby="proof-error" @else aria-describedby="proof-hint" @enderror
               class="block w-full rounded-lg border border-slate-300 text-sm text-slate-700 file:mr-4 file:border-0 file:bg-pool-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-pool-800 hover:file:bg-pool-100">
        @error('proof')
            <p id="proof-error" class="mt-1 text-sm text-rose-700">{{ $message }}</p>
        @else
            <p id="proof-hint" class="mt-1 text-sm text-slate-500">JPG, PNG, WebP or PDF, up to 5 MB. Only resort staff can see it.</p>
        @enderror
    </div>

    <x-ui.input name="reference_no" label="Transaction / reference number (optional)" placeholder="e.g. GCash reference no." />

    <x-ui.button type="submit" icon="arrow-up-tray" class="w-full sm:w-auto">{{ $submitLabel ?? 'Send payment proof' }}</x-ui.button>
</form>
