@extends('layouts.admin')

@section('title', 'Add-ons')

@section('content')
    <x-admin.page-header title="Add-ons" description="Extras guests can add to a booking, like extra hours or videoke.">
        <x-slot:actions>
            <x-ui.button :href="route('admin.add-ons.create')" icon="plus">Add add-on</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.index-filters placeholder="Search name or description" />

    @if ($addOns->isEmpty())
        <x-ui.empty-state title="No add-ons found" :description="request()->hasAny(['q', 'status']) ? 'Try a different search or filter.' : 'Add extras guests can choose when booking.'" icon="tag">
            <x-ui.button :href="route('admin.add-ons.create')" icon="plus" class="mt-4">Add add-on</x-ui.button>
        </x-ui.empty-state>
    @else
        <x-ui.card :padded="false">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-5 py-3">Add-on</th>
                            <th scope="col" class="px-5 py-3 text-right">Price</th>
                            <th scope="col" class="px-5 py-3">Status</th>
                            <th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($addOns as $addOn)
                            <tr>
                                <td class="px-5 py-3">
                                    <p class="font-medium text-slate-900">{{ $addOn->name }}</p>
                                    @if ($addOn->description)
                                        <p class="line-clamp-1 text-xs text-slate-500">{{ $addOn->description }}</p>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-right font-medium text-slate-900">{{ $addOn->formatted_price }}</td>
                                <td class="px-5 py-3">
                                    <x-ui.badge :color="$addOn->is_active ? 'garden' : 'slate'">{{ $addOn->is_active ? 'Active' : 'Inactive' }}</x-ui.badge>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3">
                                    <div class="flex justify-end gap-1">
                                        <x-ui.button :href="route('admin.add-ons.edit', $addOn)" variant="secondary" size="sm" icon="pencil-square">Edit<span class="sr-only"> {{ $addOn->name }}</span></x-ui.button>
                                        <x-admin.confirm-delete :action="route('admin.add-ons.destroy', $addOn)" :label="$addOn->name" :name="'delete-add-on-'.$addOn->id" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($addOns->hasPages())
                <x-slot:footer>{{ $addOns->links() }}</x-slot:footer>
            @endif
        </x-ui.card>
    @endif
@endsection
