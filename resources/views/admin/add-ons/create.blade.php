@extends('layouts.admin')

@section('title', 'Add add-on')

@section('content')
    <x-admin.page-header title="Add add-on" />

    <x-ui.card class="max-w-3xl">
        <form method="POST" action="{{ route('admin.add-ons.store') }}" class="space-y-6">
            @csrf
            @include('admin.add-ons._form')

            <div class="flex justify-end gap-2">
                <x-ui.button :href="route('admin.add-ons.index')" variant="secondary">Cancel</x-ui.button>
                <x-ui.button type="submit" icon="plus">Add add-on</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
