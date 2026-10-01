@extends('layouts.admin')

@section('title', 'Packages')

@section('content')
    <x-admin.page-header title="Packages" description="Bookable time slots and their base prices. Drag rows to change the order guests see.">
        <x-slot:actions>
            <x-ui.button :href="route('admin.packages.create')" icon="plus">Add package</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.index-filters placeholder="Search name, code or description" />

    @if ($packages->isEmpty())
        <x-ui.empty-state title="No packages found" :description="request()->hasAny(['q', 'status']) ? 'Try a different search or filter.' : 'Add your first package to start taking bookings.'" icon="cube">
            <x-ui.button :href="route('admin.packages.create')" icon="plus" class="mt-4">Add package</x-ui.button>
        </x-ui.empty-state>
    @else
        <x-ui.card :padded="false">
            <x-admin.sortable :url="route('admin.packages.reorder')" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="w-28 px-3 py-3"><span class="sr-only">Order</span></th>
                            <th scope="col" class="px-5 py-3">Package</th>
                            <th scope="col" class="px-5 py-3">Hours</th>
                            <th scope="col" class="px-5 py-3 text-right">Price</th>
                            <th scope="col" class="px-5 py-3 text-right">Max pax</th>
                            <th scope="col" class="px-5 py-3">Status</th>
                            <th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($packages as $package)
                            <tr data-sortable-id="{{ $package->id }}" draggable="true">
                                <td class="px-3 py-3"><x-admin.sort-handle :label="$package->name" /></td>
                                <td class="px-5 py-3">
                                    <p class="font-medium text-slate-900">{{ $package->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $package->code }} · {{ $package->bookings_count }} booking(s)</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-slate-600">
                                    {{ \Illuminate\Support\Carbon::parse($package->start_time)->format('g:i A') }}
                                    – {{ \Illuminate\Support\Carbon::parse($package->end_time)->format('g:i A') }}
                                    @if ($package->crosses_midnight)<span class="text-xs text-slate-500">(next day)</span>@endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-right font-medium text-slate-900">{{ $package->formatted_price }}</td>
                                <td class="px-5 py-3 text-right">{{ $package->max_pax }}</td>
                                <td class="px-5 py-3">
                                    <x-ui.badge :color="$package->is_active ? 'garden' : 'slate'">{{ $package->is_active ? 'Active' : 'Inactive' }}</x-ui.badge>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3">
                                    <div class="flex justify-end gap-1">
                                        <x-ui.button :href="route('admin.packages.edit', $package)" variant="secondary" size="sm" icon="pencil-square">Edit<span class="sr-only"> {{ $package->name }}</span></x-ui.button>
                                        <x-admin.confirm-delete
                                            :action="route('admin.packages.destroy', $package)"
                                            :label="$package->name"
                                            :name="'delete-package-'.$package->id"
                                            :warning="$package->bookings_count > 0 ? 'This package has bookings, so it will be kept. Deactivate it instead to hide it from guests.' : null"
                                        />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-admin.sortable>

            @if ($packages->hasPages())
                <x-slot:footer>{{ $packages->links() }}</x-slot:footer>
            @endif
        </x-ui.card>
    @endif
@endsection
