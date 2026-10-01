/**
 * Alpine component `bookingForm` — the 3-step guest booking page (resources/views/public/book/index.blade.php).
 *
 * It only DISPLAYS server data: the calendar comes from GET /book/availability and every price and
 * availability answer from POST /book/quote (AvailabilityService + PricingService). No pricing or
 * availability logic lives here. The final submit is a normal form POST to /book, which re-validates
 * everything server-side; without JavaScript the same form works with all steps visible.
 *
 * Also used by the admin walk-in and reschedule forms (M6) with admin URLs and `extra`
 * (e.g. { booking: 12 } to ignore the booking being moved).
 *
 * @param {{
 *   urls: { availability: string, quote: string },
 *   packageId: string, date: string, guestCount: number, addOns: Record<string, number>, step: number,
 *   extra?: Record<string, string|number>,
 * }} config
 */
export default function bookingForm(config) {
    return {
        step: config.step ?? 1,
        packageId: String(config.packageId ?? ''),
        date: config.date ?? '',
        guestCount: Number(config.guestCount ?? 1),
        addOns: config.addOns ?? {},
        calendar: null,
        calendarMonth: config.date ? config.date.slice(0, 7) : null,
        calendarLoading: false,
        calendarError: '',
        quote: null,
        quoting: false,
        quoteError: '',
        quoteTimer: null,
        weekdays: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],

        init() {
            this.$watch('packageId', () => { this.loadCalendar(this.calendarMonth); this.requestQuote(); });
            this.$watch('date', () => this.requestQuote());
            this.$watch('guestCount', () => this.requestQuote());
            this.$watch('addOns', () => this.requestQuote(), { deep: true });
            this.loadCalendar(this.calendarMonth);
            this.requestQuote();
        },

        /** Leading empty cells + one entry per day of the loaded month. */
        get cells() {
            if (!this.calendar) return [];
            const dates = Object.keys(this.calendar.days);
            const firstWeekday = new Date(`${dates[0]}T00:00:00`).getDay();
            return [
                ...Array.from({ length: firstWeekday }, (_, i) => ({ key: `blank-${i}`, blank: true })),
                ...dates.map((date) => ({ key: date, date, day: Number(date.slice(8)), state: this.calendar.days[date] })),
            ];
        },

        get canContinue() {
            return Boolean(this.quote && this.quote.available && !this.quoting);
        },

        async loadCalendar(month) {
            if (!this.packageId) return;
            this.calendarLoading = true;
            this.calendarError = '';
            const params = new URLSearchParams({ package_id: this.packageId, ...(config.extra ?? {}) });
            if (month) params.set('month', month);

            try {
                const response = await fetch(`${config.urls.availability}?${params}`, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error(String(response.status));
                this.calendar = await response.json();
                this.calendarMonth = this.calendar.month;
            } catch {
                this.calendarError = 'Could not load the calendar. You can still type a date below.';
            } finally {
                this.calendarLoading = false;
            }
        },

        shiftMonth(delta) {
            const [year, month] = this.calendarMonth.split('-').map(Number);
            const next = new Date(year, month - 1 + delta, 1);
            this.loadCalendar(`${next.getFullYear()}-${String(next.getMonth() + 1).padStart(2, '0')}`);
        },

        pick(cell) {
            if (cell.state === 'available') this.date = cell.date;
        },

        requestQuote() {
            clearTimeout(this.quoteTimer);
            this.quoteTimer = setTimeout(() => this.fetchQuote(), 250);
        },

        async fetchQuote() {
            if (!this.packageId || !this.date || !this.guestCount) {
                this.quote = null;
                return;
            }
            this.quoting = true;
            this.quoteError = '';

            try {
                const response = await fetch(config.urls.quote, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify({ ...(config.extra ?? {}), package_id: this.packageId, date: this.date, guest_count: this.guestCount, add_ons: this.addOns }),
                });
                const data = await response.json();

                if (response.status === 422) {
                    this.quote = null;
                    this.quoteError = Object.values(data.errors ?? {})[0]?.[0] ?? 'Please check your selection.';
                } else if (response.status === 429) {
                    this.quote = null;
                    this.quoteError = 'Too many requests. Please wait a moment and try again.';
                } else if (!response.ok) {
                    throw new Error(String(response.status));
                } else {
                    this.quote = data;
                }
            } catch {
                this.quote = null;
                this.quoteError = 'Could not check the price right now. Please try again.';
            } finally {
                this.quoting = false;
            }
        },

        go(step) {
            if (step > 1 && !this.canContinue) return;
            this.step = step;
            this.$nextTick(() => this.$refs[`step${step}`]?.focus());
        },
    };
}
