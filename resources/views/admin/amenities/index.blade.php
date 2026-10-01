@extends('layouts.admin')

@section('title', 'Amenities')

@section('content')
    <x-admin.page-header title="Amenities" description="Facilities shown on the website. Drag rows to change their order.">
        <x-slot:actions>
            <x-ui.button :href="route('admin.amenities.create')" icon="plus">Add amenity</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.index-filters placeholder="Search name or description" />

    @if ($amenities->isEmpty())
        <x-ui.empty-state title="No amenities found" :description="request()->hasAny(['q', 'status']) ? 'Try a different search or filter.' : 'Add the pools, halls and other facilities guests can enjoy.'" icon="sparkles">
            <x-ui.button :href="route('admin.amenities.create')" icon="plus" class="mt-4">Add amenity</x-ui.button>
        </x-ui.empty-state>
    @else
        <x-ui.card :padded="false">
            <x-admin.sortable :url="route('admin.amenities.reorder')" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="w-28 px-3 py-3"><span class="sr-only">Order</span></th>
                            <th scope="col" class="px-5 py-3">Amenity</th>
                            <th scope="col" class="px-5 py-3">Photo</th>
                            <th scope="col" class="px-5 py-3">Status</th>
                            <th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($amenities as $amenity)
                            <tr data-sortable-id="{{ $amenity->id }}" draggable="true">
                                <td class="px-3 py-3"><x-admin.sort-handle :label="$amenity->name" /></td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-pool-50 text-pool-700">
                                            <x-dynamic-component :component="'heroicon-o-'.$amenity->icon" class="h-5 w-5" aria-hidden="true" />
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-medium text-slate-900">{{ $amenity->name }}</p>
                                            @if ($amenity->description)
                                                <p class="line-clamp-1 text-xs text-slate-500">{{ $amenity->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3">
                                    @if ($amenity->image_path)
                                        <img src="{{ $amenity->imageUrl(\App\Enums\ImageVariant::Thumb) }}" alt="" class="h-10 w-14 rounded object-cover ring-1 ring-slate-200" loading="lazy">
                                    @else
                                        <span class="text-xs text-slate-400">None</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    <x-ui.badge :color="$amenity->is_active ? 'garden' : 'slate'">{{ $amenity->is_active ? 'Active' : 'Inactive' }}</x-ui.badge>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3">
                                    <div class="flex justify-end gap-1">
                                        <x-ui.button :href="route('admin.amenities.edit', $amenity)" variant="secondary" size="sm" icon="pencil-square">Edit<span class="sr-only"> {{ $amenity->name }}</span></x-ui.button>
                                        <x-admin.confirm-delete :action="route('admin.amenities.destroy', $amenity)" :label="$amenity->name" :name="'delete-amenity-'.$amenity->id" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-admin.sortable>

            @if ($amenities->hasPages())
                <x-slot:footer>{{ $amenities->links() }}</x-slot:footer>
            @endif
        </x-ui.card>
    @endif
@endsection
