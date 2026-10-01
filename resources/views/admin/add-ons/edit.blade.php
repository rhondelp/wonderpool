@extends('layouts.admin')

@section('title', 'Edit '.$addOn->name)

@section('content')
    <x-admin.page-header :title="'Edit '.$addOn->name" />

    <x-ui.card class="max-w-3xl">
        <form method="POST" action="{{ route('admin.add-ons.update', $addOn) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('admin.add-ons._form')

            <div class="flex justify-end gap-2">
                <x-ui.button :href="route('admin.add-ons.index')" variant="secondary">Cancel</x-ui.button>
                <x-ui.button type="submit" icon="check">Save changes</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
