{{--
    TEMPORARY design preview (local env only) — renders every UI component on the admin layout.
    Route: GET /design-preview (routes/web.php). REMOVE IN M9.
--}}
@extends('layouts.admin')

@section('title', 'Design preview')

@php
    $pool = ['bg-pool-50', 'bg-pool-100', 'bg-pool-200', 'bg-pool-300', 'bg-pool-400', 'bg-pool-500', 'bg-pool-600', 'bg-pool-700', 'bg-pool-800', 'bg-pool-900', 'bg-pool-950'];
    $garden = ['bg-garden-50', 'bg-garden-100', 'bg-garden-200', 'bg-garden-300', 'bg-garden-400', 'bg-garden-500', 'bg-garden-600', 'bg-garden-700', 'bg-garden-800', 'bg-garden-900', 'bg-garden-950'];
    $shades = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];
    $statuses = ['pending', 'awaiting_payment', 'confirmed', 'checked_in', 'completed', 'cancelled', 'rejected', 'no_show', 'unpaid', 'partial', 'paid', 'refunded'];
@endphp

@section('content')
    <x-admin.page-header title="Design preview" description="Every reusable component with the pool/garden theme. Local environment only.">
        <x-slot:actions>
            <x-ui.button variant="secondary" href="{{ url('/') }}" icon="globe-alt">Public layout</x-ui.button>
            <x-ui.button icon="plus">Primary action</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="space-y-8">
        <x-ui.card title="Palette">
            @foreach (['pool' => $pool, 'garden' => $garden] as $name => $swatches)
                <p class="mb-2 text-sm font-medium text-slate-700">{{ $name }}-*</p>
                <div class="mb-4 grid grid-cols-6 gap-2 sm:grid-cols-11">
                    @foreach ($swatches as $i => $class)
                        <div>
                            <div class="h-10 rounded-md ring-1 ring-slate-200 {{ $class }}"></div>
                            <p class="mt-1 text-center text-xs text-slate-500">{{ $shades[$i] }}</p>
                        </div>
                    @endforeach
                </div>
            @endforeach
            <p class="text-sm">Poppins
                <span class="font-normal">400</span> ·
                <span class="font-medium">500</span> ·
                <span class="font-semibold">600</span> ·
                <span class="font-bold">700</span>
            </p>
        </x-ui.card>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-admin.stat-card label="Bookings today" value="12" icon="calendar-days" />
            <x-admin.stat-card label="Checked in" value="8" icon="key" color="garden" hint="3 arriving later" />
            <x-admin.stat-card label="Pending review" value="5" icon="clock" color="amber" />
            <x-admin.stat-card label="Cancelled" value="1" icon="x-circle" color="rose" />
        </div>

        <x-ui.card title="Buttons">
            <div class="space-y-4">
                @foreach (['primary', 'secondary', 'danger', 'ghost'] as $variant)
                    <div class="flex flex-wrap items-center gap-3">
                        <x-ui.button :variant="$variant" size="sm">{{ ucfirst($variant) }} sm</x-ui.button>
                        <x-ui.button :variant="$variant" icon="check">{{ ucfirst($variant) }} md</x-ui.button>
                        <x-ui.button :variant="$variant" size="lg">{{ ucfirst($variant) }} lg</x-ui.button>
                        <x-ui.button :variant="$variant" disabled>Disabled</x-ui.button>
                    </div>
                @endforeach
            </div>
        </x-ui.card>

        <x-ui.card title="Badges">
            <div class="flex flex-wrap gap-2">
                @foreach ($statuses as $status)
                    <x-ui.badge :status="$status" />
                @endforeach
                <x-ui.badge color="garden">Featured</x-ui.badge>
                <x-ui.badge color="pool">New</x-ui.badge>
            </div>
        </x-ui.card>

        <x-ui.card title="Alerts & flash">
            <div class="space-y-3">
                <x-ui.alert type="success" title="Booking confirmed">Reference WP-2026-0001 has been confirmed.</x-ui.alert>
                <x-ui.alert type="error">The selected cottage is no longer available.</x-ui.alert>
                <x-ui.alert type="warning" dismissible>Payment proof is awaiting verification.</x-ui.alert>
                <x-ui.alert type="info" dismissible>Check-in starts at 2:00 PM.</x-ui.alert>
            </div>
            <x-slot:footer>
                <p class="text-xs text-slate-500">The flash message at the top of this page comes from x-ui.flash (session()->now in the route).</p>
            </x-slot:footer>
        </x-ui.card>

        <x-ui.card title="Form controls">
            <form class="grid gap-4 sm:grid-cols-2" x-data x-on:submit.prevent>
                <x-ui.input name="guest_name" label="Guest name" required hint="As shown on a valid ID." />
                <x-ui.input name="email" type="email" label="Email" />
                <x-ui.select name="unit_type" label="Accommodation" placeholder="Choose one..." :options="['room' => 'Room', 'cottage' => 'Cottage', 'day_use' => 'Day use']" />
                <x-ui.input name="check_in" type="date" label="Check-in date" />
                <x-ui.textarea name="notes" label="Special requests" rows="3" class="sm:col-span-2" />
                <div class="flex items-center gap-2 sm:col-span-2">
                    <input id="agree" type="checkbox" class="rounded border-slate-300 text-pool-600 focus:ring-pool-500">
                    <label for="agree" class="text-sm text-slate-700">I agree to the resort policies</label>
                </div>
            </form>
            <x-slot:footer>
                <div class="flex justify-end gap-2">
                    <x-ui.button variant="ghost">Cancel</x-ui.button>
                    <x-ui.button>Submit</x-ui.button>
                </div>
            </x-slot:footer>
        </x-ui.card>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-ui.card title="Modal">
                <p class="mb-4 text-sm text-slate-600">Opens via $dispatch('open-modal', 'demo'). Escape or backdrop closes it.</p>
                <x-ui.button variant="danger" x-data x-on:click="$dispatch('open-modal', 'demo')">Cancel booking…</x-ui.button>
            </x-ui.card>

            <x-ui.card title="Empty state" :padded="false">
                <x-ui.empty-state class="m-5" icon="calendar" title="No bookings yet" description="New booking requests will appear here.">
                    <x-ui.button size="sm" icon="plus">Create booking</x-ui.button>
                </x-ui.empty-state>
            </x-ui.card>
        </div>
    </div>

    <x-ui.modal name="demo" title="Cancel this booking?">
        This will release the reserved dates. The guest will be notified.
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'demo')">Keep booking</x-ui.button>
            <x-ui.button variant="danger">Yes, cancel</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
@endsection
