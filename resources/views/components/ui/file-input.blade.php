{{--
    x-ui.file-input — Labelled file upload with validation errors (also shows per-file errors for multiple uploads).
    The surrounding <form> needs enctype="multipart/form-data".

    @prop string      $name      Field name without brackets, e.g. "image" or "images" (required)
    @prop string|null $label     Visible label (optional)
    @prop string      $accept    accept attribute (default: jpg/png/webp images)
    @prop bool        $multiple  Allow several files; submits as name[] (default: false)
    @prop string|null $hint      Help text (optional)
    @prop bool        $required  Marks field as required (default: false)

    Usage: <x-ui.file-input name="images" label="Photos" multiple hint="Up to 20 images, 5 MB each." />
--}}
@props([
    'name',
    'label' => null,
    'accept' => 'image/jpeg,image/png,image/webp',
    'multiple' => false,
    'hint' => null,
    'required' => false,
])

@php
    $errorList = array_merge($errors->get($name), ...array_values($multiple ? $errors->get($name.'.*') : []));
@endphp

<div>
    @if ($label)
        <label for="{{ $name }}" class="mb-1 block text-sm font-medium text-slate-700">
            {{ $label }}
            @if ($required)<span class="text-rose-600" aria-hidden="true">*</span>@endif
        </label>
    @endif

    <input
        type="file"
        name="{{ $multiple ? $name.'[]' : $name }}"
        id="{{ $name }}"
        accept="{{ $accept }}"
        @if ($multiple) multiple @endif
        @required($required)
        @if ($errorList) aria-invalid="true" aria-describedby="{{ $name }}-error" @elseif ($hint) aria-describedby="{{ $name }}-hint" @endif
        {{ $attributes->merge(['class' => 'block w-full rounded-lg border border-slate-300 text-sm text-slate-700 file:mr-4 file:border-0 file:bg-pool-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-pool-800 hover:file:bg-pool-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-pool-500']) }}
    >

    @if ($errorList)
        <ul id="{{ $name }}-error" class="mt-1 space-y-0.5 text-sm text-rose-700">
            @foreach (array_unique($errorList) as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    @elseif ($hint)
        <p id="{{ $name }}-hint" class="mt-1 text-sm text-slate-500">{{ $hint }}</p>
    @endif
</div>
