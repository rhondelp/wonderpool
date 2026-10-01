{{--
    Walk-in / phone booking (D-031): same calendar + live quote as the public page (admin endpoints,
    no lead-time rule), optional payment received now, optional immediate approval.
    Owner only: price override with a mandatory reason. Data: Admin\BookingController@create.
    To add a field: add it here, to StoreWalkInRequest::rules()/walkInData(), and BookingService::create() if stored.
--}}
@extends('layouts.admin')

@section('title', 'Walk-in booking')

@php
    $config = [
        'urls' => ['availability' => route('admin.bookings.calendar'), 'quote' => route('admin.bookings.quote')],
        'packageId' => (string) old('package_id', $selectedPackage?->id),
        'date' => (string) old('date', now()->toDateString()),
        'guestCount' => (int) old('guest_count', 10),
        'addOns' => (object) collect(old('add_ons', []))->map(fn ($qty) => (int) $qty)->all(),
        'step' => 1,
    ];
@endphp

@section('content')
    <x-admin.page-header title="Walk-in booking" description="Book for a guest at the counter or on the phone. Lead time does not apply." />

    @if ($packages->isEmpty())
        <x-ui.empty-state title="No active packages" description="Activate a package first." icon="cube" />
    @else
        <form method="POST" action="{{ route('admin.bookings.store') }}" x-data="bookingForm(@js($config))" class="grid max-w-6xl gap-6 xl:grid-cols-2">
            @csrf

            <x-ui.card title="Package & date">
                <div class="space-y-5">
                    <x-ui.select name="package_id" label="Package" :options="$packages->mapWithKeys(fn ($p) => [$p->id => $p->name.' ('.$p->formatted_price.')'])->all()" :selected="(string) old('package_id', $selectedPackage?->id)" x-model="packageId" required />
                    @include('partials.availability-calendar')
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-ui.input name="date" type="date" label="Date" :value="old('date', now()->toDateString())" x-model="date" required />
                        <x-ui.input name="guest_count" type="number" min="1" label="Guests" :value="old('guest_count', 10)" x-model.number="guestCount" required />
                    </div>
                    @include('partials.quote-panel')
                </div>
            </x-ui.card>

            <div class="space-y-6">
                <x-ui.card title="Guest">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-ui.input name="guest_name" label="Full name" required />
                        <x-ui.input name="guest_phone" type="tel" label="Mobile number" placeholder="0917 123 4567" required />
                        <x-ui.input name="guest_email" type="email" label="Email (optional)" />
                        <x-ui.input name="event_type" label="Occasion (optional)" />
                    </div>
                    <div class="mt-5 space-y-5">
                        <x-ui.textarea name="notes" label="Guest notes (optional)" rows="2" />
                        <x-ui.textarea name="admin_notes" label="Internal notes (optional)" rows="2" />
                    </div>
                </x-ui.card>

                @if ($addOns->isNotEmpty())
                    <x-ui.card title="Add-ons">
                        <ul class="divide-y divide-slate-100" role="list">
                            @foreach ($addOns as $addOn)
                                <li class="flex items-center justify-between gap-4 py-2">
                                    <label for="add_on_{{ $addOn->id }}" class="text-sm"><span class="font-medium text-slate-900">{{ $addOn->name }}</span> <span class="text-slate-500">{{ $addOn->formatted_price }}</span></label>
                                    <input type="number" id="add_on_{{ $addOn->id }}" name="add_ons[{{ $addOn->id }}]" x-model.number="addOns[{{ $addOn->id }}]" value="{{ old('add_ons.'.$addOn->id, 0) }}" min="0" max="99" class="w-20 rounded-lg border-slate-300 text-sm shadow-sm focus:border-pool-500 focus:ring-pool-500">
                                </li>
                            @endforeach
                        </ul>
                    </x-ui.card>
                @endif

                <x-ui.card title="Payment received now (optional)">
                    <div class="grid gap-5 sm:grid-cols-3">
                        <x-ui.select name="payment_type" label="Type" :options="collect($paymentTypes)->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all()" selected="downpayment" />
                        <x-ui.input name="payment_amount" type="number" step="0.01" min="0" label="Amount (₱)" />
                        <x-ui.input name="payment_reference" label="Reference no." />
                    </div>
                    <div class="mt-5">
                        <x-ui.checkbox name="approve" label="Approve immediately" hint="Requires the payment above to cover the downpayment." />
                    </div>
                </x-ui.card>

                @can('overridePrice', \App\Models\Booking::class)
                    <x-ui.card title="Price override (owner only)">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-ui.input name="price_override" type="number" step="0.01" min="0" label="Total to charge (₱)" hint="Leave blank to use the quoted price." />
                            <x-ui.input name="price_override_reason" label="Reason" hint="Required with an override; recorded in the activity log." />
                        </div>
                    </x-ui.card>
                @endcan

                <div class="flex justify-end gap-2">
                    <x-ui.button :href="route('admin.bookings.index')" variant="secondary">Cancel</x-ui.button>
                    <x-ui.button type="submit" icon="check">Create booking</x-ui.button>
                </div>
            </div>
        </form>
    @endif
@endsection
