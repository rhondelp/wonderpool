{{--
    Admin booking detail: guest, schedule, price snapshot, payments (+ private proof preview), actions
    (approve / reject / cancel / complete / reschedule with confirm modals), timeline, internal notes.
    Data: Admin\BookingController@show. Allowed actions: $can (BookingService::TRANSITIONS) + policies.
--}}
@extends('layouts.admin')

@section('title', 'Booking '.$booking->reference_code)

@php
    $addOnLines = $booking->addOns->map(fn ($a) => ['name' => $a->name, 'qty' => (int) $a->pivot->quantity, 'total' => (int) $a->pivot->unit_price_cents * (int) $a->pivot->quantity]);
    $addOnsTotal = $addOnLines->sum('total');
    $money = fn (int $cents) => \App\Support\Money::format($cents);
    $rescheduleConfig = [
        'urls' => ['availability' => route('admin.bookings.calendar'), 'quote' => route('admin.bookings.quote')],
        'packageId' => (string) $booking->package_id,
        'date' => $booking->starts_at->format('Y-m-d'),
        'guestCount' => $booking->guest_count,
        'addOns' => (object) $booking->addOns->mapWithKeys(fn ($a) => [$a->id => (int) $a->pivot->quantity])->all(),
        'step' => 1,
        'extra' => ['booking' => $booking->id],
    ];
@endphp

@section('content')
    <x-admin.page-header :title="'Booking '.$booking->reference_code" :description="$booking->package->name.' · '.$windowLabel">
        <x-slot:actions>
            <x-ui.badge :status="$booking->status" class="text-sm" />
            <x-ui.badge :color="$summary['state']->color()" class="text-sm">{{ $summary['state']->label() }}</x-ui.badge>
            <x-ui.button :href="route('admin.bookings.receipt', $booking)" variant="secondary" size="sm" icon="printer" target="_blank">Receipt</x-ui.button>
            <x-ui.button :href="route('admin.bookings.receipt', [$booking, 'pdf' => 1])" variant="ghost" size="sm" icon="arrow-down-tray">PDF</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Actions --}}
    @if (in_array(true, $can, true))
        <div class="mb-6 flex flex-wrap gap-2" x-data>
            @if ($can['approve'])
                <x-ui.button icon="check-circle" x-on:click="$dispatch('open-modal', 'approve')">Approve</x-ui.button>
            @endif
            @if ($can['reschedule'])
                <x-ui.button variant="secondary" icon="calendar-days" x-on:click="$dispatch('open-modal', 'reschedule')">Reschedule</x-ui.button>
            @endif
            @if ($can['complete'])
                <x-ui.button variant="secondary" icon="flag" x-on:click="$dispatch('open-modal', 'complete')">Mark completed</x-ui.button>
            @endif
            @if ($can['reject'])
                <x-ui.button variant="danger-ghost" icon="x-circle" x-on:click="$dispatch('open-modal', 'reject')">Reject</x-ui.button>
            @endif
            @if ($can['cancel'])
                <x-ui.button variant="danger-ghost" icon="no-symbol" x-on:click="$dispatch('open-modal', 'cancel')">Cancel booking</x-ui.button>
            @endif
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            {{-- Guest & schedule --}}
            <x-ui.card title="Guest & schedule">
                <dl class="grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-slate-500">Guest</dt><dd class="font-medium text-slate-900">{{ $booking->guest_name }}</dd></div>
                    <div><dt class="text-slate-500">Mobile</dt><dd><a href="tel:{{ $booking->guest_phone }}" class="font-medium text-pool-700 hover:underline">{{ \App\Support\PhoneNumber::display($booking->guest_phone) }}</a></dd></div>
                    <div><dt class="text-slate-500">Email</dt><dd class="text-slate-900">{{ $booking->guest_email ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Occasion</dt><dd class="text-slate-900">{{ $booking->event_type ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Package</dt><dd class="text-slate-900">{{ $booking->package->name }} ({{ $booking->guest_count }} guests)</dd></div>
                    <div><dt class="text-slate-500">When</dt><dd class="text-slate-900">{{ $windowLabel }}</dd></div>
                    <div><dt class="text-slate-500">Source</dt><dd class="text-slate-900">{{ $booking->source->label() }}@if ($booking->creator) by {{ $booking->creator->name }}@endif</dd></div>
                    <div><dt class="text-slate-500">Requested</dt><dd class="text-slate-900">{{ $booking->created_at?->format('M j, Y g:i A') }}</dd></div>
                    @if ($booking->approver)
                        <div><dt class="text-slate-500">Approved</dt><dd class="text-slate-900">{{ $booking->approved_at?->format('M j, Y g:i A') }} by {{ $booking->approver->name }}</dd></div>
                    @endif
                    @if ($booking->notes)
                        <div class="sm:col-span-2"><dt class="text-slate-500">Guest notes</dt><dd class="whitespace-pre-line text-slate-900">{{ $booking->notes }}</dd></div>
                    @endif
                    @if ($booking->rejection_reason)
                        <div class="sm:col-span-2"><dt class="text-slate-500">Rejection reason</dt><dd class="text-rose-700">{{ $booking->rejection_reason }}</dd></div>
                    @endif
                    @if ($booking->cancellation_reason)
                        <div class="sm:col-span-2"><dt class="text-slate-500">Cancellation reason</dt><dd class="text-slate-900">{{ $booking->cancellation_reason }}</dd></div>
                    @endif
                </dl>
            </x-ui.card>

            {{-- Price snapshot --}}
            <x-ui.card title="Price (as booked)">
                <dl class="divide-y divide-slate-100 text-sm">
                    <div class="flex justify-between py-2"><dt class="text-slate-600">{{ $booking->package->name }} incl. date rates</dt><dd>{{ $money($booking->total_amount_cents - $addOnsTotal) }}</dd></div>
                    @foreach ($addOnLines as $line)
                        <div class="flex justify-between py-2"><dt class="text-slate-600">{{ $line['name'] }} × {{ $line['qty'] }}</dt><dd>{{ $money($line['total']) }}</dd></div>
                    @endforeach
                    @if ($booking->original_total_cents !== null)
                        <div class="flex justify-between py-2 text-amber-800"><dt>Price overridden from {{ $money($booking->original_total_cents) }}</dt><dd>{{ $booking->price_override_reason }}</dd></div>
                    @endif
                    <div class="flex justify-between py-2 font-semibold text-slate-900"><dt>Total</dt><dd>{{ $booking->formatted_total }}</dd></div>
                    <div class="flex justify-between py-2"><dt class="text-slate-600">Downpayment required</dt><dd>{{ $booking->formatted_downpayment }} @if ($summary['downpayment_covered'])<span class="text-garden-700">✓ covered</span>@endif</dd></div>
                    <div class="flex justify-between py-2"><dt class="text-slate-600">Paid (verified)</dt><dd class="text-garden-700">{{ $money($summary['verified']) }}</dd></div>
                    @if ($summary['pending'] > 0)
                        <div class="flex justify-between py-2"><dt class="text-slate-600">Awaiting review</dt><dd class="text-amber-700">{{ $money($summary['pending']) }}</dd></div>
                    @endif
                    <div class="flex justify-between py-2 font-semibold"><dt>Balance due</dt><dd @class(['text-rose-700' => $summary['balance_due'] > 0, 'text-garden-700' => $summary['balance_due'] === 0])>{{ $money($summary['balance_due']) }}</dd></div>
                </dl>
            </x-ui.card>

            {{-- Payments --}}
            <x-ui.card title="Payments" :padded="false">
                <x-slot:actions>
                    @can('recordPayment', $booking)
                        <x-ui.button size="sm" variant="secondary" icon="plus" x-data x-on:click="$dispatch('open-modal', 'record-payment')">Record payment</x-ui.button>
                    @endcan
                </x-slot:actions>
                @if ($booking->payments->isEmpty())
                    <p class="p-5 text-sm text-slate-500">No payments yet.</p>
                @else
                    <ul class="divide-y divide-slate-100" role="list">
                        @foreach ($booking->payments->sortByDesc('id') as $payment)
                            <li class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between" x-data>
                                <div class="min-w-0 text-sm">
                                    <p class="font-medium text-slate-900">{{ $payment->formatted_amount }} · {{ $payment->type->label() }} <x-ui.badge :status="$payment->status" class="ml-1" /></p>
                                    <p class="text-slate-500">
                                        {{ $payment->created_at?->format('M j, Y g:i A') }}
                                        @if ($payment->reference_no) · Ref {{ $payment->reference_no }} @endif
                                        @if ($payment->recorder) · recorded by {{ $payment->recorder->name }} @elseif ($payment->hasProof()) · uploaded by guest @endif
                                        @if ($payment->verifier && $payment->status !== \App\Enums\PaymentStatus::Pending) · {{ $payment->status->label() }} by {{ $payment->verifier->name }} @endif
                                    </p>
                                    @if ($payment->notes)<p class="text-slate-600">{{ $payment->notes }}</p>@endif
                                    @if ($payment->rejection_reason)<p class="text-rose-700">Reason: {{ $payment->rejection_reason }}</p>@endif
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @if ($payment->hasProof())
                                        @can('viewProof', $payment)
                                            <x-ui.button size="sm" variant="secondary" icon="eye" x-on:click="$dispatch('open-modal', 'proof-{{ $payment->id }}')">View proof</x-ui.button>
                                        @endcan
                                    @endif
                                    @if ($payment->status === \App\Enums\PaymentStatus::Pending)
                                        @can('verify', $payment)
                                            <form method="POST" action="{{ route('admin.payments.verify', $payment) }}">
                                                @csrf @method('PATCH')
                                                <x-ui.button type="submit" size="sm" icon="check">Verify</x-ui.button>
                                            </form>
                                        @endcan
                                    @endif
                                    @if ($payment->status !== \App\Enums\PaymentStatus::Rejected)
                                        @can('reject', $payment)
                                            <x-ui.button size="sm" variant="danger-ghost" icon="x-mark" x-on:click="$dispatch('open-modal', 'reject-payment-{{ $payment->id }}')">{{ $payment->status === \App\Enums\PaymentStatus::Verified ? 'Void' : 'Reject' }}</x-ui.button>
                                        @endcan
                                    @endif
                                </div>

                                @if ($payment->hasProof())
                                    <x-ui.modal :name="'proof-'.$payment->id" title="Payment proof" max-width="xl">
                                        @if ($payment->proofIsPdf())
                                            <iframe src="{{ route('admin.payments.proof', $payment) }}" title="Payment proof PDF" class="h-[70vh] w-full rounded-lg ring-1 ring-slate-200" loading="lazy"></iframe>
                                        @else
                                            <img src="{{ route('admin.payments.proof', $payment) }}" alt="Payment proof for {{ $booking->reference_code }}" loading="lazy" class="mx-auto max-h-[70vh] rounded-lg">
                                        @endif
                                        <x-slot:footer>
                                            <x-ui.button :href="route('admin.payments.proof', $payment)" variant="secondary" target="_blank" icon="arrow-top-right-on-square">Open in new tab</x-ui.button>
                                        </x-slot:footer>
                                    </x-ui.modal>
                                @endif

                                <x-ui.modal :name="'reject-payment-'.$payment->id" :title="$payment->status === \App\Enums\PaymentStatus::Verified ? 'Void verified payment?' : 'Reject payment?'">
                                    <form id="reject-payment-{{ $payment->id }}-form" method="POST" action="{{ route('admin.payments.reject', $payment) }}" class="space-y-3">
                                        @csrf @method('PATCH')
                                        <p>{{ $payment->formatted_amount }} will not count toward this booking.</p>
                                        <x-ui.textarea name="reason" :id="'reject-payment-reason-'.$payment->id" label="Reason (shown to the guest)" rows="3" required />
                                    </form>
                                    <x-slot:footer>
                                        <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'reject-payment-{{ $payment->id }}')">Keep</x-ui.button>
                                        <x-ui.button type="submit" variant="danger" form="reject-payment-{{ $payment->id }}-form">{{ $payment->status === \App\Enums\PaymentStatus::Verified ? 'Void payment' : 'Reject payment' }}</x-ui.button>
                                    </x-slot:footer>
                                </x-ui.modal>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>
        </div>

        <div class="space-y-6">
            {{-- Internal notes --}}
            <x-ui.card title="Internal notes">
                <form method="POST" action="{{ route('admin.bookings.notes', $booking) }}" class="space-y-3">
                    @csrf @method('PATCH')
                    <x-ui.textarea name="admin_notes" :value="$booking->admin_notes" rows="4" hint="Only admins see these notes." />
                    <x-ui.button type="submit" size="sm" variant="secondary" icon="check">Save notes</x-ui.button>
                </form>
            </x-ui.card>

            {{-- Timeline --}}
            <x-ui.card title="Timeline">
                @if ($timeline === [])
                    <p class="text-sm text-slate-500">No activity yet.</p>
                @else
                    <ol class="relative space-y-4 border-l-2 border-slate-100 pl-5 text-sm">
                        @foreach ($timeline as $entry)
                            <li class="relative">
                                <span class="absolute -left-[27px] top-1.5 h-3 w-3 rounded-full bg-pool-600 ring-4 ring-white" aria-hidden="true"></span>
                                <p class="font-medium text-slate-900">{{ $entry['label'] }}</p>
                                <p class="text-xs text-slate-500">{{ $entry['at']->format('M j, Y g:i A') }} · {{ $entry['actor'] }}</p>
                                @foreach ($entry['details'] as $detail)
                                    <p class="text-slate-600">{{ $detail }}</p>
                                @endforeach
                            </li>
                        @endforeach
                    </ol>
                @endif
            </x-ui.card>
        </div>
    </div>

    {{-- Modals --}}
    @if ($can['approve'])
        <x-ui.modal name="approve" title="Approve this booking?">
            <form id="approve-form" method="POST" action="{{ route('admin.bookings.approve', $booking) }}">@csrf</form>
            <p>Pending payment proofs will be marked as verified. Approval needs verified payments of at least <strong>{{ $booking->formatted_downpayment }}</strong>.</p>
            <p class="mt-2">Verified so far: <strong>{{ $money($summary['verified']) }}</strong>@if ($summary['pending'] > 0), awaiting review: <strong>{{ $money($summary['pending']) }}</strong>@endif.</p>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'approve')">Not yet</x-ui.button>
                <x-ui.button type="submit" form="approve-form" icon="check-circle">Approve booking</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif

    @if ($can['reject'])
        <x-ui.modal name="reject" title="Reject this booking?" :show="$errors->has('reason') && old('_action') === 'reject'">
            <form id="reject-form" method="POST" action="{{ route('admin.bookings.reject', $booking) }}" class="space-y-3">
                @csrf <input type="hidden" name="_action" value="reject">
                <p>The guest sees the reason on the tracking page. The date becomes free again.</p>
                <x-ui.textarea name="reason" id="reject-reason" label="Reason" rows="3" required />
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'reject')">Keep booking</x-ui.button>
                <x-ui.button type="submit" variant="danger" form="reject-form">Reject booking</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif

    @if ($can['cancel'])
        <x-ui.modal name="cancel" title="Cancel this booking?">
            <form id="cancel-form" method="POST" action="{{ route('admin.bookings.cancel', $booking) }}" class="space-y-3">
                @csrf
                <p>The date becomes free again. Recorded payments stay on file (handle refunds outside the system).</p>
                <x-ui.textarea name="reason" id="cancel-reason" label="Reason (optional, shown to the guest)" rows="3" />
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'cancel')">Keep booking</x-ui.button>
                <x-ui.button type="submit" variant="danger" form="cancel-form">Cancel booking</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif

    @if ($can['complete'])
        <x-ui.modal name="complete" title="Mark as completed?">
            <form id="complete-form" method="POST" action="{{ route('admin.bookings.complete', $booking) }}">@csrf</form>
            <p>Balance due: <strong>{{ $money($summary['balance_due']) }}</strong>. Record any remaining payment first if it was collected.</p>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'complete')">Not yet</x-ui.button>
                <x-ui.button type="submit" form="complete-form" icon="flag">Mark completed</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif

    @if ($can['reschedule'])
        <x-ui.modal name="reschedule" title="Reschedule booking" max-width="lg">
            <form id="reschedule-form" method="POST" action="{{ route('admin.bookings.reschedule', $booking) }}" class="space-y-4" x-data="bookingForm(@js($rescheduleConfig))">
                @csrf
                <x-ui.select name="package_id" id="reschedule-package" label="Package" :options="$packages->mapWithKeys(fn ($p) => [$p->id => $p->name])->all()" :selected="(string) $booking->package_id" x-model="packageId" />
                @include('partials.availability-calendar')
                <x-ui.input name="date" id="reschedule-date" type="date" label="New date" :value="$booking->starts_at->format('Y-m-d')" x-model="date" required />
                @include('partials.quote-panel')
                <x-ui.checkbox name="reprice" id="reschedule-reprice" label="Recalculate the price with today's rates" hint="Otherwise the booked price is kept." />
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'reschedule')">Cancel</x-ui.button>
                <x-ui.button type="submit" form="reschedule-form" icon="calendar-days">Move booking</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif

    @can('recordPayment', $booking)
        <x-ui.modal name="record-payment" title="Record a payment" :show="$errors->hasAny(['amount', 'type'])">
            <form id="record-payment-form" method="POST" action="{{ route('admin.bookings.payments.store', $booking) }}" class="space-y-4">
                @csrf
                <x-ui.select name="type" label="Type" :options="collect($paymentTypes)->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all()" :selected="$summary['verified'] >= $booking->downpayment_required_cents ? 'balance' : 'downpayment'" required />
                <x-ui.input name="amount" type="number" step="0.01" min="0.01" label="Amount received (₱)" :value="number_format(\App\Support\Money::toPesos(max($summary['balance_due'], 0)), 2, '.', '')" required />
                <x-ui.input name="reference_no" label="Reference no. (optional)" />
                <x-ui.textarea name="notes" id="payment-notes" label="Note (optional)" rows="2" />
                <p class="text-sm text-slate-500">Recorded payments count as verified immediately.</p>
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'record-payment')">Cancel</x-ui.button>
                <x-ui.button type="submit" form="record-payment-form" icon="banknotes">Record payment</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endcan
@endsection
