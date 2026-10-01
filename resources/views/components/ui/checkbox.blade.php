{{--
    x-ui.checkbox — Labelled boolean checkbox. Always submits a value: a hidden "0" precedes the "1" checkbox.

    @prop string      $name     Field name (required)
    @prop string      $label    Visible label (required)
    @prop bool        $checked  Initial state when there is no old input (default: false)
    @prop string|null $id       Element id (default: $name)
    @prop string|null $hint     Help text under the label (optional)

    Usage: <x-ui.checkbox name="is_active" label="Active" :checked="$package->is_active" />
--}}
@props([
    'name',
    'label',
    'checked' => false,
    'id' => null,
    'hint' => null,
])

@php
    $id = $id ?? $name;
    $isChecked = (bool) old($name, $checked);
    $error = $errors->first($name);
@endphp

<div>
    <input type="hidden" name="{{ $name }}" value="0">
    <label for="{{ $id }}" class="flex items-start gap-3">
        <input
            type="checkbox"
            name="{{ $name }}"
            id="{{ $id }}"
            value="1"
            @checked($isChecked)
            @if ($hint) aria-describedby="{{ $id }}-hint" @endif
            {{ $attributes->merge(['class' => 'mt-0.5 h-4 w-4 rounded border-slate-300 text-pool-700 focus:ring-pool-500']) }}
        >
        <span>
            <span class="block text-sm font-medium text-slate-700">{{ $label }}</span>
            @if ($hint)
                <span id="{{ $id }}-hint" class="block text-sm text-slate-500">{{ $hint }}</span>
            @endif
        </span>
    </label>
    @if ($error)
        <p class="mt-1 text-sm text-rose-700">{{ $error }}</p>
    @endif
</div>
