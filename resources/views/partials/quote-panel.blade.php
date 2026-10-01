{{--
    Live availability + price panel for the Alpine `bookingForm` component (server JSON only).
    Shared by the public booking page and the admin walk-in / reschedule forms (M6).
--}}
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

