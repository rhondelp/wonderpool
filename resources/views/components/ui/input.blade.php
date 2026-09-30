{{--
    x-ui.input — Labelled text input with validation error and hint; repopulates from old().

    @prop string      $name      Field name (required); also used for old() and $errors lookup
    @prop string|null $label     Visible label (optional)
    @prop string      $type      Input type (default: text)
    @prop string|null $id        Element id (default: $name)
    @prop mixed       $value     Initial value when there is no old input (optional)
    @prop string|null $hint      Help text shown below the field (optional)
    @prop bool        $required  Marks field as required (default: false)

    Usage: <x-ui.input name="email" type="email" label="Email" required />
--}}
@props([
    'name',
    'label' => null,
    'type' => 'text',
    'id' => null,
    'value' => null,
    'hint' => null,
    'required' => false,
])

@php
    $id = $id ?? $name;
    $error = $errors->first($name);
@endphp

<div>
    @if ($label)
        <label for="{{ $id }}" class="mb-1 block text-sm font-medium text-slate-700">
            {{ $label }}
            @if ($required)<span class="text-rose-600" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $id }}"
        @if ($type !== 'password') value="{{ old($name, $value) }}" @endif
        @required($required)
        @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif ($hint) aria-describedby="{{ $id }}-hint" @endif
        {{ $attributes->class([
            'block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-pool-500 focus:ring-pool-500',
            'border-rose-500 focus:border-rose-500 focus:ring-rose-500' => $error,
        ]) }}
    >

    @if ($error)
        <p id="{{ $id }}-error" class="mt-1 text-sm text-rose-700">{{ $error }}</p>
    @elseif ($hint)
        <p id="{{ $id }}-hint" class="mt-1 text-sm text-slate-500">{{ $hint }}</p>
    @endif
</div>
