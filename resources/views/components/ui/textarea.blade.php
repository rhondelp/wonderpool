{{--
    x-ui.textarea — Labelled multi-line input with validation error; repopulates from old().

    @prop string      $name      Field name (required)
    @prop string|null $label     Visible label (optional)
    @prop string|null $id        Element id (default: $name)
    @prop mixed       $value     Initial value when there is no old input (optional)
    @prop int         $rows      Visible rows (default: 4)
    @prop string|null $hint      Help text (optional)
    @prop bool        $required  Marks field as required (default: false)

    Usage: <x-ui.textarea name="notes" label="Special requests" rows="3" />
--}}
@props([
    'name',
    'label' => null,
    'id' => null,
    'value' => null,
    'rows' => 4,
    'hint' => null,
    'required' => false,
])

@php
    $id = $id ?? $name;
    $key = str_replace(['[', ']'], ['.', ''], $name); // "a[b]" → "a.b" for old() and $errors
    $error = $errors->first($key);
@endphp

<div>
    @if ($label)
        <label for="{{ $id }}" class="mb-1 block text-sm font-medium text-slate-700">
            {{ $label }}
            @if ($required)<span class="text-rose-600" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <textarea
        name="{{ $name }}"
        id="{{ $id }}"
        rows="{{ $rows }}"
        @required($required)
        @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif ($hint) aria-describedby="{{ $id }}-hint" @endif
        {{ $attributes->class([
            'block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-pool-500 focus:ring-pool-500',
            'border-rose-500 focus:border-rose-500 focus:ring-rose-500' => $error,
        ]) }}
    >{{ old($key, $value) }}</textarea>

    @if ($error)
        <p id="{{ $id }}-error" class="mt-1 text-sm text-rose-700">{{ $error }}</p>
    @elseif ($hint)
        <p id="{{ $id }}-hint" class="mt-1 text-sm text-slate-500">{{ $hint }}</p>
    @endif
</div>
