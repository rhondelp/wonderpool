@extends('layouts.admin')

@section('title', 'Edit '.$user->name)

@section('content')
    <x-admin.page-header :title="'Edit '.$user->name" :description="$user->email" />

    <div class="grid max-w-4xl gap-6 lg:grid-cols-2">
        <x-ui.card title="Details">
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <x-ui.input name="name" label="Full name" :value="$user->name" required />
                <x-ui.input name="email" type="email" label="Email" :value="$user->email" required />
                @if ($user->is(auth()->user()))
                    <input type="hidden" name="role" value="{{ $user->role->value }}">
                    <p class="text-sm text-slate-500">Role: <strong class="font-medium text-slate-700">{{ $user->role->label() }}</strong> (you cannot change your own role)</p>
                @else
                    <x-ui.select name="role" label="Role" :options="collect($roles)->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all()" :selected="$user->role->value" required />
                @endif

                <div class="flex justify-end gap-2">
                    <x-ui.button :href="route('admin.users.index')" variant="secondary">Cancel</x-ui.button>
                    <x-ui.button type="submit" icon="check">Save</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        @can('resetPassword', $user)
            <x-ui.card title="Reset password">
                <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <p class="text-sm text-slate-600">Set a temporary password. {{ $user->name }} must change it on their next sign-in.</p>
                    <x-ui.input name="password" id="reset-password" type="password" label="Temporary password" autocomplete="new-password" required />
                    <x-ui.input name="password_confirmation" id="reset-password-confirmation" type="password" label="Confirm temporary password" autocomplete="new-password" required />

                    <div class="flex justify-end">
                        <x-ui.button type="submit" variant="danger" icon="key">Set temporary password</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        @else
            <x-ui.card title="Your password">
                <p class="text-sm text-slate-600">Change your own password from the account menu.</p>
                <x-ui.button :href="route('admin.password.edit')" variant="secondary" icon="key" class="mt-4">Change password</x-ui.button>
            </x-ui.card>
        @endcan
    </div>
@endsection
