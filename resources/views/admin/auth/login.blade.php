@extends('layouts.auth')

@section('title', 'Admin sign in')
@section('heading', 'Sign in to the admin panel')

@section('content')
    <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-5">
        @csrf

        <x-ui.input name="email" type="email" label="Email" autocomplete="username" required autofocus />
        <x-ui.input name="password" type="password" label="Password" autocomplete="current-password" required />

        <label for="remember" class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" id="remember" name="remember" value="1" @checked(old('remember')) class="rounded border-slate-300 text-pool-700 focus:ring-pool-500">
            Keep me signed in
        </label>

        <x-ui.button type="submit" class="w-full">Sign in</x-ui.button>
    </form>

    <p class="mt-6 text-center text-xs text-slate-500">Forgot your password? Ask the resort owner to set a temporary one.</p>
@endsection
