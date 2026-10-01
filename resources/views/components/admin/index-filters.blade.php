{{--
    x-admin.index-filters — GET search + state filter bar above admin index tables.
    Reads/keeps the current query string values `q` and `status`.

    @prop string $placeholder  Search box placeholder (default: "Search…")
    @prop array  $states       [value => label] for the status select (default: All / Active / Inactive)
    @slot default              Extra filter controls (optional), e.g. a category select

    Usage: <x-admin.index-filters placeholder="Search packages" />
--}}
@props([
    'placeholder' => 'Search…',
    'states' => ['' => 'All', 'active' => 'Active', 'inactive' => 'Inactive'],
])

<form method="GET" role="search" {{ $attributes->merge(['class' => 'mb-4 flex flex-col gap-3 sm:flex-row sm:items-end']) }}>
    <div class="flex-1">
        <label for="filter-q" class="sr-only">Search</label>
        <div class="relative">
            <x-heroicon-m-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" aria-hidden="true" />
            <input type="search" id="filter-q" name="q" value="{{ request('q') }}" placeholder="{{ $placeholder }}" class="block w-full rounded-lg border-slate-300 pl-10 text-sm shadow-sm focus:border-pool-500 focus:ring-pool-500">
        </div>
    </div>

    <div>
        <label for="filter-status" class="sr-only">Status</label>
        <select id="filter-status" name="status" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-pool-500 focus:ring-pool-500 sm:w-40">
            @foreach ($states as $value => $text)
                <option value="{{ $value }}" @selected((string) request('status') === (string) $value)>{{ $text }}</option>
            @endforeach
        </select>
    </div>

    {{ $slot }}

    <div class="flex gap-2">
        <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
        @if (request()->hasAny(['q', 'status', 'category']))
            <x-ui.button :href="url()->current()" variant="ghost">Clear</x-ui.button>
        @endif
    </div>
</form>
