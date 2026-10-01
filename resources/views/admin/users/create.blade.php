@extends('layouts.admin')

@section('title', 'Add user')

@section('content')
    <x-admin.page-header title="Add user" description="The new user signs in with a temporary password and must change it right away." />

    <x-ui.card class="max-w-2xl">
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5">
            @csrf

            <x-ui.input name="name" label="Full name" autocomplete="off" required />
            <x-ui.input name="email" type="email" label="Email" autocomplete="off" required />
            <x-ui.select name="role" label="Role" :options="collect($roles)->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all()" selected="staff" hint="Staff can only manage bookings." required />
            <x-ui.input name="password" type="password" label="Temporary password" autocomplete="new-password" hint="At least 8 characters with letters and numbers. Share it privately." required />
            <x-ui.input name="password_confirmation" type="password" label="Confirm temporary password" autocomplete="new-password" required />

            <div class="flex justify-end gap-2">
                <x-ui.button :href="route('admin.users.index')" variant="secondary">Cancel</x-ui.button>
                <x-ui.button type="submit" icon="user-plus">Add user</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
