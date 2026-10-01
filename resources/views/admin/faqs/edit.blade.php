@extends('layouts.admin')

@section('title', 'Edit '.'FAQ')

@section('content')
    <x-admin.page-header :title="'Edit '.'FAQ'" />

    <x-ui.card class="max-w-3xl">
        <form method="POST" action="{{ route('admin.faqs.update', $faq) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('admin.faqs._form')

            <div class="flex justify-end gap-2">
                <x-ui.button :href="route('admin.faqs.index')" variant="secondary">Cancel</x-ui.button>
                <x-ui.button type="submit" icon="check">Save changes</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
