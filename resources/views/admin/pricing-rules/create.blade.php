@extends('layouts.admin')

@section('title', 'Add pricing rule')

@section('content')
    <x-admin.page-header title="Add pricing rule" />

    <x-ui.card class="max-w-3xl">
        <form method="POST" action="{{ route('admin.pricing-rules.store') }}" class="space-y-6">
            @csrf
            @include('admin.pricing-rules._form')

            <div class="flex justify-end gap-2">
                <x-ui.button :href="route('admin.pricing-rules.index')" variant="secondary">Cancel</x-ui.button>
                <x-ui.button type="submit" icon="plus">Add rule</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
