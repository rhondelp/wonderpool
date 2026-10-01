@extends('layouts.admin')

@section('title', 'Edit '.$rule->name)

@section('content')
    <x-admin.page-header :title="'Edit '.$rule->name" />

    <x-ui.card class="max-w-3xl">
        <form method="POST" action="{{ route('admin.pricing-rules.update', $rule) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('admin.pricing-rules._form')

            <div class="flex justify-end gap-2">
                <x-ui.button :href="route('admin.pricing-rules.index')" variant="secondary">Cancel</x-ui.button>
                <x-ui.button type="submit" icon="check">Save changes</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
