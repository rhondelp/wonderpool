@extends('layouts.admin')

@section('title', 'Block dates')

@section('content')
    <x-admin.page-header title="Block dates" />

    <x-ui.card class="max-w-3xl">
        <form method="POST" action="{{ route('admin.blocked-dates.store') }}" class="space-y-6">
            @csrf
            @include('admin.blocked-dates._form')

            <div class="flex justify-end gap-2">
                <x-ui.button :href="route('admin.blocked-dates.index')" variant="secondary">Cancel</x-ui.button>
                <x-ui.button type="submit" icon="plus">Save block</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
