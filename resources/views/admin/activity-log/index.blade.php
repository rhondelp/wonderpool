{{--
    Owner-only activity log: filters (actor, module, action, dates, search) and newest-first entries.
    Data: Admin\ActivityLogController (ActivityLogService, D-036).
--}}
@extends('layouts.admin')

@section('title', 'Activity log')

@section('content')
    <x-admin.page-header title="Activity log" description="Who did what and when. Entries cannot be edited or deleted." />

    <form method="GET" role="search" class="mb-4 grid gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200 sm:grid-cols-2 lg:grid-cols-6">
        <div class="lg:col-span-2">
            <x-ui.input name="q" type="search" label="Search" :value="request('q')" hint="Booking reference or any detail" />
        </div>
        <x-ui.select name="user" label="Who" :options="$actors" :selected="request('user')" placeholder="Anyone" />
        <x-ui.select name="module" label="Area" :options="$modules" :selected="request('module')" placeholder="All areas" />
        <x-ui.input name="from" type="date" label="From" :value="request('from')" />
        <x-ui.input name="to" type="date" label="To" :value="request('to')" />
        <div class="sm:col-span-2 lg:col-span-2">
            <x-ui.select name="action" label="Action" :options="$actions" :selected="request('action')" placeholder="Any action" />
        </div>
        <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-4">
            <x-ui.button type="submit" variant="secondary" icon="funnel">Filter</x-ui.button>
            @if (request()->hasAny(['q', 'user', 'module', 'action', 'from', 'to']))
                <x-ui.button :href="route('admin.activity-log')" variant="ghost">Clear</x-ui.button>
            @endif
        </div>
    </form>

    @if ($entries->isEmpty())
        <x-ui.empty-state title="No activity found" description="Try other filters." icon="clipboard-document-list" />
    @else
        <x-ui.card :padded="false">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3">When</th>
                            <th scope="col" class="px-4 py-3">Who</th>
                            <th scope="col" class="px-4 py-3">Action</th>
                            <th scope="col" class="px-4 py-3">On</th>
                            <th scope="col" class="px-4 py-3">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 align-top">
                        @foreach ($entries as $entry)
                            @php
                                $label = $logs->subjectLabel($entry);
                                $url = $logs->subjectUrl($entry);
                                $details = $logs->details($entry);
                            @endphp
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">
                                    <time datetime="{{ $entry->created_at->toIso8601String() }}">{{ $entry->created_at->format('M j, Y') }}<br><span class="text-xs">{{ $entry->created_at->format('g:i:s A') }}</span></time>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $entry->user->name ?? ($entry->user_id ? 'Deleted user' : 'Guest / system') }}</td>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $logs->actionLabel($entry->action) }}<br><span class="font-mono text-xs font-normal text-slate-500">{{ $entry->action }}</span></td>
                                <td class="px-4 py-3">
                                    @if ($url)
                                        <a href="{{ $url }}" class="text-pool-800 hover:underline">{{ $label }}</a>
                                    @else
                                        {{ $label ?? '—' }}
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($details === [])
                                        <span class="text-slate-400">—</span>
                                    @else
                                        <dl class="grid max-w-md grid-cols-[auto_1fr] gap-x-3 gap-y-0.5 text-xs">
                                            @foreach ($details as $key => $value)
                                                <dt class="text-slate-500">{{ $key }}</dt>
                                                <dd class="break-words text-slate-800">{{ \Illuminate\Support\Str::limit($value, 200) }}</dd>
                                            @endforeach
                                        </dl>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        <div class="mt-4">{{ $entries->links() }}</div>
    @endif
@endsection
