{{--
    Gallery image edit form. To add a field: add it here, to UpdateGalleryImageRequest::rules(),
    the gallery_images migration and GalleryImage::$fillable.
--}}
@extends('layouts.admin')

@section('title', 'Edit image')

@section('content')
    <x-admin.page-header title="Edit image" />

    <div class="grid max-w-5xl gap-6 lg:grid-cols-2">
        <img src="{{ $image->imageUrl() }}" alt="{{ $image->caption ?? 'Gallery image' }}" class="w-full rounded-xl object-cover shadow-sm ring-1 ring-slate-200">

        <x-ui.card>
            <form method="POST" action="{{ route('admin.gallery.update', $image) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <x-ui.input name="caption" label="Caption" :value="$image->caption" />
                <x-ui.select name="category" label="Category" :options="collect($categories)->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()" :selected="$image->category->value" required />
                <x-ui.checkbox name="is_visible" label="Show on the website" :checked="$image->is_visible" />

                <div class="flex justify-end gap-2">
                    <x-ui.button :href="route('admin.gallery.index')" variant="secondary">Cancel</x-ui.button>
                    <x-ui.button type="submit" icon="check">Save changes</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
@endsection
