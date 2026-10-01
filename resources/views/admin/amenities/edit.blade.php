@extends('layouts.admin')

@section('title', 'Edit '.$amenity->name)

@section('content')
    <x-admin.page-header :title="'Edit '.$amenity->name" />

    <x-ui.card class="max-w-3xl">
        <form method="POST" action="{{ route('admin.amenities.update', $amenity) }}" class="space-y-6" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('admin.amenities._form')

            <div class="flex justify-end gap-2">
                <x-ui.button :href="route('admin.amenities.index')" variant="secondary">Cancel</x-ui.button>
                <x-ui.button type="submit" icon="check">Save changes</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
