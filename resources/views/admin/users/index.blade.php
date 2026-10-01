@extends('layouts.admin')

@section('title', 'Users')

@section('content')
    <x-admin.page-header title="Users" description="Owners have full access. Staff can only manage bookings.">
        <x-slot:actions>
            <x-ui.button :href="route('admin.users.create')" icon="user-plus">Add user</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-ui.card :padded="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3">Name</th>
                        <th scope="col" class="px-5 py-3">Role</th>
                        <th scope="col" class="px-5 py-3">Status</th>
                        <th scope="col" class="px-5 py-3">Last sign-in</th>
                        <th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($users as $user)
                        <tr>
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-900">
                                    {{ $user->name }}
                                    @if ($user->is(auth()->user())) <span class="text-xs font-normal text-slate-500">(you)</span> @endif
                                </p>
                                <p class="text-slate-500">{{ $user->email }}</p>
                            </td>
                            <td class="px-5 py-3"><x-ui.badge :status="$user->role" /></td>
                            <td class="px-5 py-3">
                                @if ($user->is_active)
                                    <x-ui.badge color="garden">Active</x-ui.badge>
                                @else
                                    <x-ui.badge color="slate">Disabled</x-ui.badge>
                                @endif
                                @if ($user->must_change_password)
                                    <x-ui.badge color="amber" class="ml-1">Temp. password</x-ui.badge>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $user->last_login_at?->format('M j, Y g:i A') ?? 'Never' }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <x-ui.button :href="route('admin.users.edit', $user)" variant="secondary" size="sm" icon="pencil-square">Edit</x-ui.button>
                                    @can('toggleActive', $user)
                                        <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}">
                                            @csrf
                                            @method('PATCH')
                                            <x-ui.button type="submit" :variant="$user->is_active ? 'ghost' : 'secondary'" size="sm">
                                                {{ $user->is_active ? 'Disable' : 'Enable' }}<span class="sr-only"> {{ $user->name }}</span>
                                            </x-ui.button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <x-slot:footer>{{ $users->links() }}</x-slot:footer>
        @endif
    </x-ui.card>
@endsection
