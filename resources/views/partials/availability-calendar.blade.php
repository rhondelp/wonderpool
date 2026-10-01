{{--
    Availability month calendar for the Alpine `bookingForm` component (resources/js/booking.js).
    Shared by the public booking page and the admin walk-in / reschedule forms (M6).
    Needs the surrounding x-data="bookingForm(...)" scope (calendar, cells, date, pick(), shiftMonth()).
    Hidden until Alpine starts (x-cloak); a plain date input next to it is the no-JS fallback.
--}}
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

