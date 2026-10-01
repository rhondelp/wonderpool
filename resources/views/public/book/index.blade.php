{{--
    3-step booking page (single page, Alpine `bookingForm` in resources/js/booking.js).
    Step 1: package, date (availability calendar), guests → live quote.
    Step 2: guest details + optional add-ons.
    Step 3: summary, payment instructions, policies, confirm (normal POST to book.store).
    Without JavaScript all three fieldsets show and the single form still validates server-side.
    To add a field: add it to the right fieldset here, to StoreBookingRequest::rules()/bookingData(),
    and (if stored) to BookingService::create().
    Data: Public\BookingController@create.
--}}
@extends('layouts.public')

@section('title', 'Book your stay')
@section('meta_description', 'Check real-time availability, see your exact price and send your booking request online.')

@php
    $step = $errors->hasAny(['terms', 'website', 'cf-turnstile-response']) ? 3
        : ($errors->hasAny(['guest_name', 'guest_phone', 'guest_email', 'event_type', 'notes']) || $errors->has('add_ons.*') ? 2 : 1);
    $config = [
        'urls' => ['availability' => route('book.availability'), 'quote' => route('book.quote')],
        'packageId' => (string) old('package_id', $selectedPackage?->id),
        'date' => (string) old('date', ''),
        'guestCount' => (int) old('guest_count', 10),
        'addOns' => (object) collect(old('add_ons', []))->map(fn ($qty) => (int) $qty)->all(),
        'step' => $step,
    ];
    $inputClass = 'block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-pool-500 focus:ring-pool-500';
@endphp

@section('content')
    <x-public.page-hero title="Book your stay" subtitle="Pick a package and date, see your price instantly, then send your request. The whole resort is reserved for you." />

    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        @if ($packages->isEmpty())
            <x-ui.empty-state title="Online booking is not open yet" description="Please contact us to book." icon="calendar">
                <x-ui.button :href="route('contact')" class="mt-4">Contact us</x-ui.button>
            </x-ui.empty-state>
        @else
            <form method="POST" action="{{ route('book.store') }}" x-data="bookingForm(@js($config))" novalidate class="space-y-8">
                @csrf

                {{-- Step indicator (JS only) --}}
                <ol x-cloak class="flex items-center gap-2 text-sm font-medium" aria-label="Booking steps">
                    @foreach ([1 => 'Date & package', 2 => 'Your details', 3 => 'Confirm'] as $number => $label)
                        <li class="flex flex-1 items-center gap-2">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full ring-1" x-bind:class="step >= {{ $number }} ? 'bg-pool-700 text-white ring-pool-700' : 'bg-white text-slate-500 ring-slate-300'" x-bind:aria-current="step === {{ $number }} ? 'step' : null">{{ $number }}</span>
                            <span class="hidden sm:inline" x-bind:class="step === {{ $number }} ? 'text-pool-900' : 'text-slate-500'">{{ $label }}</span>
                            @if ($number < 3)<span class="h-px flex-1 bg-slate-200" aria-hidden="true"></span>@endif
                        </li>
                    @endforeach
                </ol>

                {{-- STEP 1 --}}
                <fieldset x-show="step === 1" class="space-y-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-8">
                    <legend x-ref="step1" tabindex="-1" class="sr-only">Step 1: choose a package, date and number of guests</legend>
                    <h2 class="text-lg font-semibold text-pool-900">1. Choose your package and date</h2>

                    <div>
                        <p class="mb-2 text-sm font-medium text-slate-700">Package</p>
                        <div class="grid gap-3 sm:grid-cols-3" role="radiogroup" aria-label="Package">
                            @foreach ($packages as $package)
                                <label class="cursor-pointer rounded-2xl p-4 ring-1 ring-slate-300 transition has-[:checked]:bg-pool-50 has-[:checked]:ring-2 has-[:checked]:ring-pool-600 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-pool-500">
                                    <input type="radio" name="package_id" value="{{ $package->id }}" x-model="packageId" class="sr-only" @checked((string) old('package_id', $selectedPackage?->id) === (string) $package->id)>
                                    <span class="block font-semibold text-pool-900">{{ $package->name }}</span>
                                    <span class="mt-1 block text-sm text-slate-600">{{ \Illuminate\Support\Carbon::parse($package->start_time)->format('g:i A') }} – {{ \Illuminate\Support\Carbon::parse($package->end_time)->format('g:i A') }}@if ($package->crosses_midnight) (next day)@endif</span>
                                    <span class="mt-1 block text-sm text-slate-600">Up to {{ $package->max_pax }} guests · from <strong class="text-pool-800">{{ $package->formatted_price }}</strong></span>
                                </label>
                            @endforeach
                        </div>
                        @error('package_id')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                    </div>

                    {{-- Calendar (JS) --}}
                    <div x-cloak class="rounded-2xl ring-1 ring-slate-200">
                        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                            <button type="button" x-on:click="shiftMonth(-1)" x-bind:disabled="!calendar || !calendar.has_previous" class="rounded-lg p-2 text-pool-800 hover:bg-pool-50 disabled:opacity-30"><span class="sr-only">Previous month</span><x-heroicon-o-chevron-left class="h-5 w-5" aria-hidden="true" /></button>
                            <p class="font-semibold text-pool-900" x-text="calendar ? calendar.label : 'Loading…'" aria-live="polite"></p>
                            <button type="button" x-on:click="shiftMonth(1)" x-bind:disabled="!calendar || !calendar.has_next" class="rounded-lg p-2 text-pool-800 hover:bg-pool-50 disabled:opacity-30"><span class="sr-only">Next month</span><x-heroicon-o-chevron-right class="h-5 w-5" aria-hidden="true" /></button>
                        </div>
                        <div class="p-3" x-bind:aria-busy="calendarLoading">
                            <div class="grid grid-cols-7 gap-1 text-center text-xs font-medium text-slate-500" aria-hidden="true">
                                <template x-for="name in weekdays" :key="name"><span x-text="name"></span></template>
                            </div>
                            <div class="mt-1 grid grid-cols-7 gap-1" role="grid" aria-label="Available dates">
                                <template x-for="cell in cells" :key="cell.key">
                                    <div role="gridcell">
                                        <template x-if="!cell.blank">
                                            <button
                                                type="button"
                                                x-on:click="pick(cell)"
                                                x-bind:disabled="cell.state !== 'available'"
                                                x-bind:aria-pressed="date === cell.date"
                                                x-bind:aria-label="cell.date + (cell.state === 'available' ? ' available' : cell.state === 'booked' ? ' booked' : ' not bookable')"
                                                x-bind:class="{
                                                    'bg-pool-700 text-white ring-pool-700': date === cell.date,
                                                    'bg-white text-slate-800 ring-slate-200 hover:bg-pool-50 hover:ring-pool-400': cell.state === 'available' && date !== cell.date,
                                                    'bg-rose-50 text-rose-700 line-through ring-rose-100': cell.state === 'booked',
                                                    'bg-slate-50 text-slate-400 ring-transparent': cell.state === 'closed',
                                                }"
                                                class="flex h-10 w-full items-center justify-center rounded-lg text-sm ring-1 focus:outline-none focus-visible:ring-2 focus-visible:ring-pool-500 disabled:cursor-not-allowed sm:h-12"
                                                x-text="cell.day"
                                            ></button>
                                        </template>
                                    </div>
                                </template>
                            </div>
                            <p x-show="calendarError" class="mt-2 text-sm text-rose-700" x-text="calendarError"></p>
                            <ul class="mt-3 flex flex-wrap gap-4 text-xs text-slate-600">
                                <li class="flex items-center gap-1.5"><span class="h-3 w-3 rounded ring-1 ring-slate-300"></span> Available</li>
                                <li class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-rose-100"></span> Booked / closed</li>
                                <li class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-slate-100"></span> Not bookable online</li>
                                <li class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-pool-700"></span> Your date</li>
                            </ul>
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="date" class="mb-1 block text-sm font-medium text-slate-700">Date <span class="text-rose-600" aria-hidden="true">*</span></label>
                            <input type="date" id="date" name="date" x-model="date" value="{{ old('date') }}" min="{{ now()->toDateString() }}" required class="{{ $inputClass }}" @error('date') aria-invalid="true" aria-describedby="date-error" @enderror>
                            @error('date')<p id="date-error" class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="guest_count" class="mb-1 block text-sm font-medium text-slate-700">Number of guests <span class="text-rose-600" aria-hidden="true">*</span></label>
                            <input type="number" id="guest_count" name="guest_count" x-model.number="guestCount" value="{{ old('guest_count', 10) }}" min="1" max="1000" required class="{{ $inputClass }}" @error('guest_count') aria-invalid="true" aria-describedby="guest_count-error" @enderror>
                            @error('guest_count')<p id="guest_count-error" class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- Live quote (JS) --}}
                    <div x-cloak aria-live="polite" class="rounded-2xl bg-gradient-to-br from-pool-50 to-garden-50 p-5 ring-1 ring-pool-100">
                        <p x-show="!date" class="text-sm text-slate-600">Pick a date to see availability and your price.</p>
                        <p x-show="quoting" class="text-sm text-slate-600">Checking availability…</p>
                        <p x-show="quoteError && !quoting" class="text-sm font-medium text-rose-700" x-text="quoteError"></p>
                        <template x-if="quote && !quoting">
                            <div>
                                <p class="text-sm text-slate-700" x-text="quote.window ? quote.window.label : ''"></p>
                                <p x-show="!quote.available" class="mt-2 font-medium text-rose-700" x-text="quote.reason"></p>
                                <template x-if="quote.available && quote.breakdown">
                                    <div class="mt-3 flex flex-wrap items-end justify-between gap-3">
                                        <p><span class="block text-xs uppercase tracking-wide text-slate-500">Total</span><span class="text-2xl font-semibold text-pool-900" x-text="quote.breakdown.total_formatted"></span></p>
                                        <p class="text-sm text-slate-700">Downpayment (<span x-text="quote.breakdown.downpayment_percent"></span>%): <strong x-text="quote.breakdown.downpayment_formatted"></strong></p>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <div x-cloak class="flex justify-end">
                        <x-ui.button x-on:click="go(2)" x-bind:disabled="!canContinue" icon="arrow-right">Continue</x-ui.button>
                    </div>
                </fieldset>

                {{-- STEP 2 --}}
                <fieldset x-show="step === 2" class="space-y-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-8">
                    <legend x-ref="step2" tabindex="-1" class="sr-only">Step 2: your details and add-ons</legend>
                    <h2 class="text-lg font-semibold text-pool-900">2. Your details</h2>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-ui.input name="guest_name" label="Full name" autocomplete="name" required />
                        <x-ui.input name="guest_phone" type="tel" label="Mobile number" autocomplete="tel" inputmode="tel" placeholder="0917 123 4567" hint="We'll use this to confirm your booking. You'll need it to track your booking." required />
                        <x-ui.input name="guest_email" type="email" label="Email (optional)" autocomplete="email" />
                        <x-ui.input name="event_type" label="Occasion (optional)" placeholder="Birthday, family reunion, company outing…" />
                    </div>
                    <x-ui.textarea name="notes" label="Notes or special requests (optional)" rows="3" />

                    @if ($addOns->isNotEmpty())
                        <div>
                            <p class="mb-2 text-sm font-medium text-slate-700">Add-ons (optional)</p>
                            <ul class="divide-y divide-slate-100 rounded-2xl ring-1 ring-slate-200" role="list">
                                @foreach ($addOns as $addOn)
                                    <li class="flex items-center justify-between gap-4 p-4">
                                        <label for="add_on_{{ $addOn->id }}" class="min-w-0">
                                            <span class="block font-medium text-slate-900">{{ $addOn->name }}</span>
                                            <span class="block text-sm text-slate-600">{{ $addOn->formatted_price }} each @if ($addOn->description)· {{ $addOn->description }}@endif</span>
                                        </label>
                                        <input type="number" id="add_on_{{ $addOn->id }}" name="add_ons[{{ $addOn->id }}]" x-model.number="addOns[{{ $addOn->id }}]" value="{{ old('add_ons.'.$addOn->id, 0) }}" min="0" max="99" class="w-20 rounded-lg border-slate-300 text-sm shadow-sm focus:border-pool-500 focus:ring-pool-500">
                                    </li>
                                @endforeach
                            </ul>
                            @error('add_ons.*')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                        </div>
                    @endif

                    <div x-cloak class="flex justify-between">
                        <x-ui.button variant="secondary" x-on:click="go(1)" icon="arrow-left">Back</x-ui.button>
                        <x-ui.button x-on:click="go(3)" x-bind:disabled="!canContinue" icon="arrow-right">Review booking</x-ui.button>
                    </div>
                </fieldset>

                {{-- STEP 3 --}}
                <fieldset x-show="step === 3" class="space-y-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-8">
                    <legend x-ref="step3" tabindex="-1" class="sr-only">Step 3: review and confirm</legend>
                    <h2 class="text-lg font-semibold text-pool-900">3. Review and confirm</h2>

                    {{-- Summary from the server quote (JS) --}}
                    <template x-if="quote && quote.breakdown">
                        <dl class="divide-y divide-slate-100 rounded-2xl text-sm ring-1 ring-slate-200" aria-label="Price summary">
                            <div class="flex justify-between gap-4 p-4"><dt class="text-slate-600">When</dt><dd class="text-right font-medium text-slate-900" x-text="quote.window.label"></dd></div>
                            <div class="flex justify-between gap-4 p-4"><dt class="text-slate-600">Base price</dt><dd x-text="quote.breakdown.base_price_formatted"></dd></div>
                            <template x-for="adjustment in quote.breakdown.adjustments" :key="adjustment.rule_id">
                                <div class="flex justify-between gap-4 p-4"><dt class="text-slate-600" x-text="adjustment.label"></dt><dd x-text="adjustment.delta_formatted"></dd></div>
                            </template>
                            <template x-for="line in quote.breakdown.add_ons" :key="line.add_on_id">
                                <div class="flex justify-between gap-4 p-4"><dt class="text-slate-600" x-text="line.name + ' × ' + line.quantity"></dt><dd x-text="line.line_total_formatted"></dd></div>
                            </template>
                            <div class="flex justify-between gap-4 bg-pool-50 p-4 text-base font-semibold text-pool-900"><dt>Total</dt><dd x-text="quote.breakdown.total_formatted"></dd></div>
                            <div class="flex justify-between gap-4 p-4"><dt class="text-slate-600">Downpayment due now (<span x-text="quote.breakdown.downpayment_percent"></span>%)</dt><dd class="font-semibold" x-text="quote.breakdown.downpayment_formatted"></dd></div>
                        </dl>
                    </template>

                    @if ($paymentInstructions)
                        <div class="rounded-2xl bg-amber-50 p-5 ring-1 ring-amber-200">
                            <h3 class="font-semibold text-amber-900">How to pay</h3>
                            <x-ui.prose :text="$paymentInstructions" class="mt-2 text-sm" />
                            <p class="mt-2 text-sm text-amber-900">After confirming, you'll upload a screenshot of your payment receipt.</p>
                        </div>
                    @endif

                    @if ($cancellationPolicy)
                        <details class="rounded-2xl p-4 ring-1 ring-slate-200">
                            <summary class="cursor-pointer font-medium text-pool-900">Cancellation &amp; rescheduling policy</summary>
                            <x-ui.prose :text="$cancellationPolicy" class="mt-3 text-sm" />
                        </details>
                    @endif

                    {{-- Honeypot: humans never see or fill this field (D-028). --}}
                    <div class="absolute -left-[9999px] h-px w-px overflow-hidden" aria-hidden="true">
                        <label for="website">Leave this field empty</label>
                        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    @if ($turnstile)
                        <div class="cf-turnstile" data-sitekey="{{ config('wonderpool.turnstile.site_key') }}"></div>
                        @error('cf-turnstile-response')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
                        @push('scripts')
                            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                        @endpush
                    @endif

                    <x-ui.checkbox name="terms" label="I have read the house rules and payment instructions." :hint="null" />
                    <p class="text-sm"><a href="{{ route('policies') }}" target="_blank" class="font-medium text-pool-700 underline">Read the house rules</a></p>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
                        <x-ui.button x-cloak variant="secondary" x-on:click="go(2)" icon="arrow-left">Back</x-ui.button>
                        <x-ui.button type="submit" size="lg" icon="check">Confirm booking</x-ui.button>
                    </div>
                </fieldset>
            </form>
        @endif
    </div>
@endsection
