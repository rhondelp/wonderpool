{{--
    x-admin.confirm-delete — Delete button that opens a confirmation modal, then submits DELETE to $action.

    @prop string      $action   Destroy URL (required)
    @prop string      $label    What is being deleted, shown in the dialog, e.g. "Day Package (A)" (required)
    @prop string      $name     Unique modal name on the page, e.g. "delete-package-3" (required)
    @prop string|null $warning  Extra sentence under the question (optional)
    @prop string      $size     Trigger button size sm|md (default: sm)

    Usage: <x-admin.confirm-delete :action="route('admin.faqs.destroy', $faq)" :label="$faq->question" :name="'delete-faq-'.$faq->id" />
--}}
@props([
    'action',
    'label',
    'name',
    'warning' => null,
    'size' => 'sm',
])

<div x-data class="inline-flex">
    <x-ui.button variant="danger-ghost" :size="$size" icon="trash" x-on:click="$dispatch('open-modal', {{ Js::from($name) }})">
        Delete<span class="sr-only"> {{ $label }}</span>
    </x-ui.button>

    <x-ui.modal :name="$name" title="Delete this item?" max-width="sm">
        <p>You are about to delete <strong class="font-semibold text-slate-900">{{ $label }}</strong>. This cannot be undone.</p>
        @if ($warning)
            <p class="mt-2 text-slate-600">{{ $warning }}</p>
        @endif

        <form id="{{ $name }}-form" method="POST" action="{{ $action }}">
            @csrf
            @method('DELETE')
        </form>

        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', {{ Js::from($name) }})">Keep it</x-ui.button>
            <x-ui.button type="submit" variant="danger" form="{{ $name }}-form">Delete</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
