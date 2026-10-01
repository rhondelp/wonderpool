{{--
    Blocked-date fields shared by create/edit. To add a field: add it here, to BlockedDateRequest::rules()/payload(),
    a blocked_dates migration and BlockedDate::$fillable.

    @param \App\Models\BlockedDate $block
--}}
@php
    $whole = (bool) old('whole_days', $block->exists ? $block->isWholeDays() : true);
@endphp

<div class="space-y-5" x-data="{ whole: @js($whole) }">
    <x-ui.input name="reason" label="Reason" :value="$block->reason" hint="Shown to staff only, e.g. Pool maintenance, Private family event." required />

    <x-ui.checkbox name="whole_days" label="Whole day(s)" :checked="$whole" x-model="whole" hint="Blocks from midnight of the first day to midnight after the last day." />

    <div x-show="whole" class="grid gap-5 sm:grid-cols-2">
        <x-ui.input name="start_date" type="date" label="First day" :value="$block->exists ? $block->starts_at->format('Y-m-d') : null" x-bind:required="whole" />
        <x-ui.input name="end_date" type="date" label="Last day (inclusive)" :value="$block->exists ? $block->ends_at->copy()->subSecond()->format('Y-m-d') : null" x-bind:required="whole" />
    </div>

    <div x-show="!whole" x-cloak class="grid gap-5 sm:grid-cols-2">
        <x-ui.input name="starts_at" type="datetime-local" label="Starts" :value="$block->exists ? $block->starts_at->format('Y-m-d\TH:i') : null" x-bind:required="!whole" />
        <x-ui.input name="ends_at" type="datetime-local" label="Ends" :value="$block->exists ? $block->ends_at->format('Y-m-d\TH:i') : null" hint="The block ends at this moment (a booking may start exactly then)." x-bind:required="!whole" />
    </div>

    <p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800 ring-1 ring-amber-200">
        Blocking dates does not cancel existing bookings. If any overlap, you will see their references after saving.
    </p>
</div>
