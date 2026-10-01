@extends('layouts.auth')

@section('title', 'Change password')
@section('heading', 'Change your password')

@section('content')
    <form method="POST" action="{{ route('admin.password.update') }}" class="space-y-5">
        @csrf
        @method('PUT')

        <x-ui.input name="current_password" type="password" label="Current password" autocomplete="current-password" required />
        <x-ui.input name="password" type="password" label="New password" autocomplete="new-password" hint="At least 8 characters with letters and numbers." required />
        <x-ui.input name="password_confirmation" type="password" label="Confirm new password" autocomplete="new-password" required />

        <x-ui.button type="submit" class="w-full">Save new password</x-ui.button>
    </form>

    <div class="mt-6 flex items-center justify-between text-sm">
        @unless (auth()->user()?->must_change_password)
            <a href="{{ route('admin.dashboard') }}" class="font-medium text-pool-700 hover:text-pool-800">Back to dashboard</a>
        @endunless
        <form method="POST" action="{{ route('admin.logout') }}" class="ml-auto">
            @csrf
            <button type="submit" class="font-medium text-slate-600 hover:text-slate-900">Sign out</button>
        </form>
    </div>
@endsection
