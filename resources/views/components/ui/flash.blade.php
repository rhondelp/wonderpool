{{--
    x-ui.flash — Renders session flash messages as dismissible alerts. Included once in each layout.
    Controllers flash with: ->with('success'|'error'|'warning'|'info', 'Message').

    No props.
--}}
@php
    $messages = collect(['success', 'error', 'warning', 'info'])
        ->filter(fn (string $type) => session()->has($type))
        ->mapWithKeys(fn (string $type) => [$type => session($type)]);
@endphp

@if ($messages->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'space-y-3']) }}>
        @foreach ($messages as $type => $message)
            <x-ui.alert :type="$type" dismissible>{{ $message }}</x-ui.alert>
        @endforeach
    </div>
@endif
