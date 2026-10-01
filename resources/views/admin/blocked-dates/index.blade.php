@extends('layouts.admin')

@section('title', 'Blocked dates')

@section('content')
    <x-admin.page-header title="Blocked dates" description="Close the resort for maintenance or private use. Guests cannot book blocked times.">
        <x-slot:actions>
            <x-ui.button :href="route('admin.blocked-dates.create')" icon="plus">Block dates</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.index-filters placeholder="Search reasons" :states="['' => 'Upcoming', 'past' => 'Past', 'all' => 'All']" />

    @if ($blocks->isEmpty())
        <x-ui.empty-state title="No blocked dates" :description="request()->hasAny(['q', 'status']) ? 'Try a different search or filter.' : 'The resort is open for booking on every date.'" icon="no-symbol">
            <x-ui.button :href="route('admin.blocked-dates.create')" icon="plus" class="mt-4">Block dates</x-ui.button>
        </x-ui.empty-state>
    @else
        <x-ui.card :padded="false">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-5 py-3">When</th>
                            <th scope="col" class="px-5 py-3">Reason</th>
                            <th scope="col" class="px-5 py-3">Added by</th>
                            <th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($blocks as $block)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-3 font-medium text-slate-900">
                                    @if ($block->isWholeDays())
                                        {{ $block->starts_at->format('M j, Y') }}
                                        @if ($block->ends_at->copy()->subDay()->gt($block->starts_at)) – {{ $block->ends_at->copy()->subDay()->format('M j, Y') }} @endif
                                        <span class="block text-xs font-normal text-slate-500">Whole day(s)</span>
                                    @else
                                        {{ $block->starts_at->format('M j, Y g:i A') }} – {{ $block->ends_at->format('M j, Y g:i A') }}
                                    @endif
                                </td>
                                <td class="px-5 py-3">{{ $block->reason }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $block->creator?->name ?? '—' }}</td>
                                <td class="whitespace-nowrap px-5 py-3">
                                    <div class="flex justify-end gap-1">
                                        <x-ui.button :href="route('admin.blocked-dates.edit', $block)" variant="secondary" size="sm" icon="pencil-square">Edit<span class="sr-only"> {{ $block->adminLabel() }}</span></x-ui.button>
                                        <x-admin.confirm-delete :action="route('admin.blocked-dates.destroy', $block)" :label="$block->adminLabel()" :name="'delete-block-'.$block->id" warning="Those dates become bookable again." />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($blocks->hasPages())
                <x-slot:footer>{{ $blocks->links() }}</x-slot:footer>
            @endif
        </x-ui.card>
    @endif
@endsection
