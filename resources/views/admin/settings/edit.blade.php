@extends('layouts.admin')

@section('title', 'Settings · '.$group->label())

@section('content')
    <x-admin.page-header title="Settings" description="Business rules, payment instructions and contact details shown to guests." />

    <div class="grid gap-6 lg:grid-cols-[14rem_1fr]">
        <nav aria-label="Settings sections">
            <ul class="flex gap-2 overflow-x-auto lg:flex-col lg:gap-1">
                @foreach ($groups as $tab)
                    <li class="shrink-0">
                        <a
                            href="{{ route('admin.settings.edit', $tab) }}"
                            @if ($tab === $group) aria-current="page" @endif
                            @class([
                                'flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium',
                                'bg-pool-700 text-white' => $tab === $group,
                                'text-slate-700 hover:bg-white' => $tab !== $group,
                            ])
                        >
                            <x-dynamic-component :component="'heroicon-o-'.$tab->icon()" class="h-5 w-5" aria-hidden="true" />
                            {{ $tab->label() }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <x-ui.card :title="$group->label()">
            <form method="POST" action="{{ route('admin.settings.update', $group) }}" class="space-y-5">
                @csrf
                @method('PUT')

                @foreach ($group->fields() as $key => $field)
                    @php
                        // Dotted key "booking.downpayment_percent" is submitted as booking[downpayment_percent].
                        [$prefix, $name] = explode('.', $key, 2);
                        $inputName = $prefix.'['.$name.']';
                        $inputId = str_replace('.', '-', $key);
                    @endphp

                    @if ($field['type'] === 'textarea')
                        <x-ui.textarea :name="$inputName" :id="$inputId" :label="$field['label']" :value="$values[$key]" :hint="$field['hint'] ?? null" rows="4" />
                    @else
                        <x-ui.input :name="$inputName" :id="$inputId" :type="$field['type']" :label="$field['label']" :value="$values[$key]" :hint="$field['hint'] ?? null" />
                    @endif
                @endforeach

                <div class="flex justify-end">
                    <x-ui.button type="submit" icon="check">Save {{ mb_strtolower($group->label()) }}</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
@endsection
