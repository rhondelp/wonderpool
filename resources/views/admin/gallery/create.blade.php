{{--
    Gallery upload form. To add a field: add it here, to StoreGalleryImagesRequest::rules()
    and GalleryService::upload().
--}}
@extends('layouts.admin')

@section('title', 'Upload images')

@section('content')
    <x-admin.page-header title="Upload images" description="Select several photos at once. Large versions and thumbnails are created automatically." />

    <x-ui.card class="max-w-3xl">
        <form method="POST" action="{{ route('admin.gallery.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <x-ui.file-input name="images" label="Images" multiple required :hint="'JPG, PNG or WebP, up to 5 MB each, '.\App\Http\Requests\Admin\Content\StoreGalleryImagesRequest::MAX_FILES.' at a time.'" />
            <x-ui.select name="category" label="Category" :options="collect($categories)->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()" placeholder="Choose a category" required />
            <x-ui.input name="caption" label="Caption (optional)" hint="Applied to every image in this upload. You can edit captions one by one later." />
            <x-ui.checkbox name="is_visible" label="Show on the website" :checked="true" />

            <div class="flex justify-end gap-2">
                <x-ui.button :href="route('admin.gallery.index')" variant="secondary">Cancel</x-ui.button>
                <x-ui.button type="submit" icon="arrow-up-tray">Upload</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
