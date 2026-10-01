@extends('layouts.admin')

@section('title', 'Edit '.$package->name)

@section('content')
    <x-admin.page-header :title="'Edit '.$package->name" description="Changes apply to new bookings. Existing bookings keep their times and price." />

    <x-ui.card class="max-w-3xl">
        <form method="POST" action="{{ route('admin.packages.update', $package) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('admin.packages._form')

            <div class="flex justify-end gap-2">
                <x-ui.button :href="route('admin.packages.index')" variant="secondary">Cancel</x-ui.button>
                <x-ui.button type="submit" icon="check">Save changes</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
