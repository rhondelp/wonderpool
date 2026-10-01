{{--
    x-ui.select — Labelled select with validation error; repopulates from old().

    @prop string      $name         Field name (required)
    @prop array       $options      [value => label] pairs (default: [])
    @prop string|null $label        Visible label (optional)
    @prop string|null $id           Element id (default: $name)
    @prop mixed       $selected     Selected value when there is no old input (optional)
    @prop string|null $placeholder  Empty first option text (optional)
    @prop string|null $hint         Help text (optional)
    @prop bool        $required     Marks field as required (default: false)

    Usage: <x-ui.select name="type" label="Type" :options="['room' => 'Room']" placeholder="Choose..." />
--}}
@props([
    'name',
    'options' => [],
    'label' => null,
    'id' => null,
    'selected' => null,
    'placeholder' => null,
    'hint' => null,
    'required' => false,
])

@php
    $id = $id ?? $name;
    $key = str_replace(['[', ']'], ['.', ''], $name); // "a[b]" → "a.b" for old() and $errors
    $error = $errors->first($key);
    $current = (string) old($key, $selected);
@endphp

<div>
    @if ($label)
        <label for="{{ $id }}" class="mb-1 block text-sm font-medium text-slate-700">
            {{ $label }}
            @if ($required)<span class="text-rose-600" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <select
        name="{{ $name }}"
        id="{{ $id }}"
        @required($required)
        @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif ($hint) aria-describedby="{{ $id }}-hint" @endif
        {{ $attributes->class([
            'block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-pool-500 focus:ring-pool-500',
            'border-rose-500 focus:border-rose-500 focus:ring-rose-500' => $error,
        ]) }}
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $value => $text)
            <option value="{{ $value }}" @selected($current === (string) $value)>{{ $text }}</option>
        @endforeach
    </select>

    @if ($error)
        <p id="{{ $id }}-error" class="mt-1 text-sm text-rose-700">{{ $error }}</p>
    @elseif ($hint)
        <p id="{{ $id }}-hint" class="mt-1 text-sm text-slate-500">{{ $hint }}</p>
    @endif
</div>
