@extends('layouts.admin')

@section('title', 'Add package')

@section('content')
    <x-admin.page-header title="Add package" />

    <x-ui.card class="max-w-3xl">
        <form method="POST" action="{{ route('admin.packages.store') }}" class="space-y-6">
            @csrf
            @include('admin.packages._form')

            <div class="flex justify-end gap-2">
                <x-ui.button :href="route('admin.packages.index')" variant="secondary">Cancel</x-ui.button>
                <x-ui.button type="submit" icon="plus">Add package</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
