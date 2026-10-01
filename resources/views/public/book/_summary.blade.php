{{--
    Booking summary card (stored snapshot: totals and add-on unit prices from the booking).

    @param \App\Models\Booking $booking  With package and addOns loaded
    @param string $windowLabel
--}}
<dl class="divide-y divide-slate-100 rounded-2xl bg-white text-sm shadow-sm ring-1 ring-slate-200">
    <div class="flex justify-between gap-4 p-4"><dt class="text-slate-600">Reference</dt><dd class="font-mono font-semibold tracking-wider text-pool-900">{{ $booking->reference_code }}</dd></div>
    <div class="flex justify-between gap-4 p-4"><dt class="text-slate-600">Status</dt><dd><x-ui.badge :status="$booking->status" /></dd></div>
    <div class="flex justify-between gap-4 p-4"><dt class="text-slate-600">Package</dt><dd class="text-right font-medium text-slate-900">{{ $booking->package->name }}</dd></div>
    <div class="flex justify-between gap-4 p-4"><dt class="text-slate-600">When</dt><dd class="text-right text-slate-900">{{ $windowLabel }}</dd></div>
    <div class="flex justify-between gap-4 p-4"><dt class="text-slate-600">Guests</dt><dd class="text-slate-900">{{ $booking->guest_count }}</dd></div>
    @if ($booking->relationLoaded('addOns'))
        @foreach ($booking->addOns as $addOn)
            <div class="flex justify-between gap-4 p-4"><dt class="text-slate-600">{{ $addOn->name }} × {{ $addOn->pivot->quantity }}</dt><dd>{{ \App\Support\Money::format($addOn->pivot->unit_price_cents * $addOn->pivot->quantity) }}</dd></div>
        @endforeach
    @endif
    <div class="flex justify-between gap-4 bg-pool-50 p-4 text-base font-semibold text-pool-900"><dt>Total</dt><dd>{{ $booking->formatted_total }}</dd></div>
    <div class="flex justify-between gap-4 p-4"><dt class="text-slate-600">Downpayment due</dt><dd class="font-semibold text-slate-900">{{ $booking->formatted_downpayment }}</dd></div>
</dl>
