@extends('layouts.admin')

@section('title', 'Gallery')

@section('content')
    <x-admin.page-header title="Gallery" description="Photos shown on the website. Drag images to change their order.">
        <x-slot:actions>
            <x-ui.button :href="route('admin.gallery.create')" icon="arrow-up-tray">Upload images</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.index-filters placeholder="Search captions" :states="['' => 'All', 'active' => 'Visible', 'inactive' => 'Hidden']">
        <div>
            <label for="filter-category" class="sr-only">Category</label>
            <select id="filter-category" name="category" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-pool-500 focus:ring-pool-500 sm:w-40">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->value }}" @selected(request('category') === $category->value)>{{ $category->label() }}</option>
                @endforeach
            </select>
        </div>
    </x-admin.index-filters>

    @if ($images->isEmpty())
        <x-ui.empty-state title="No images found" :description="request()->hasAny(['q', 'status', 'category']) ? 'Try a different search or filter.' : 'Upload photos of the pools, rooms and events.'" icon="photo">
            <x-ui.button :href="route('admin.gallery.create')" icon="arrow-up-tray" class="mt-4">Upload images</x-ui.button>
        </x-ui.empty-state>
    @else
        <x-admin.sortable :url="route('admin.gallery.reorder')" axis="x">
            <ul class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4" role="list">
                @foreach ($images as $image)
                    <li data-sortable-id="{{ $image->id }}" draggable="true" class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                        <div class="relative aspect-[4/3] bg-slate-100">
                            <img src="{{ $image->imageUrl(\App\Enums\ImageVariant::Thumb) }}" alt="{{ $image->caption ?? '' }}" class="h-full w-full object-cover" loading="lazy" draggable="false">
                            @unless ($image->is_visible)
                                <span class="absolute left-2 top-2"><x-ui.badge color="slate">Hidden</x-ui.badge></span>
                            @endunless
                        </div>
                        <div class="space-y-2 p-3">
                            <div class="flex items-center justify-between gap-2">
                                <x-ui.badge color="pool">{{ $image->category->label() }}</x-ui.badge>
                                <x-admin.sort-handle :label="$image->adminLabel()" />
                            </div>
                            <p class="line-clamp-1 text-sm text-slate-700">{{ $image->caption ?? 'No caption' }}</p>
                            <div class="flex flex-wrap gap-1">
                                <x-ui.button :href="route('admin.gallery.edit', $image)" variant="secondary" size="sm" icon="pencil-square">Edit<span class="sr-only"> {{ $image->adminLabel() }}</span></x-ui.button>
                                <form method="POST" action="{{ route('admin.gallery.toggle-visibility', $image) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-ui.button type="submit" variant="ghost" size="sm" :icon="$image->is_visible ? 'eye-slash' : 'eye'">
                                        {{ $image->is_visible ? 'Hide' : 'Show' }}<span class="sr-only"> {{ $image->adminLabel() }}</span>
                                    </x-ui.button>
                                </form>
                                <x-admin.confirm-delete :action="route('admin.gallery.destroy', $image)" :label="$image->adminLabel()" :name="'delete-image-'.$image->id" />
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-admin.sortable>

        @if ($images->hasPages())
            <div class="mt-6">{{ $images->links() }}</div>
        @endif
    @endif
@endsection
