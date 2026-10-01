@extends('layouts.admin')

@section('title', 'Edit blocked dates')

@section('content')
    <x-admin.page-header :title="'Edit blocked dates'" />

    <x-ui.card class="max-w-3xl">
        <form method="POST" action="{{ route('admin.blocked-dates.update', $block) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('admin.blocked-dates._form')

            <div class="flex justify-end gap-2">
                <x-ui.button :href="route('admin.blocked-dates.index')" variant="secondary">Cancel</x-ui.button>
                <x-ui.button type="submit" icon="check">Save changes</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
