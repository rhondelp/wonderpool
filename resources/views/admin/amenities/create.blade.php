@extends('layouts.admin')

@section('title', 'Add amenity')

@section('content')
    <x-admin.page-header title="Add amenity" />

    <x-ui.card class="max-w-3xl">
        <form method="POST" action="{{ route('admin.amenities.store') }}" class="space-y-6" enctype="multipart/form-data">
            @csrf
            @include('admin.amenities._form')

            <div class="flex justify-end gap-2">
                <x-ui.button :href="route('admin.amenities.index')" variant="secondary">Cancel</x-ui.button>
                <x-ui.button type="submit" icon="plus">Add amenity</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
