@extends('layouts.admin')

@section('title', 'Add FAQ')

@section('content')
    <x-admin.page-header title="Add FAQ" />

    <x-ui.card class="max-w-3xl">
        <form method="POST" action="{{ route('admin.faqs.store') }}" class="space-y-6">
            @csrf
            @include('admin.faqs._form')

            <div class="flex justify-end gap-2">
                <x-ui.button :href="route('admin.faqs.index')" variant="secondary">Cancel</x-ui.button>
                <x-ui.button type="submit" icon="plus">Add FAQ</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
