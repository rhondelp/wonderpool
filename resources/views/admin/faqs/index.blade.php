@extends('layouts.admin')

@section('title', 'FAQs')

@section('content')
    <x-admin.page-header title="FAQs" description="Questions and answers shown on the website. Drag rows to change their order.">
        <x-slot:actions>
            <x-ui.button :href="route('admin.faqs.create')" icon="plus">Add FAQ</x-ui.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.index-filters placeholder="Search questions and answers" />

    @if ($faqs->isEmpty())
        <x-ui.empty-state title="No FAQs found" :description="request()->hasAny(['q', 'status']) ? 'Try a different search or filter.' : 'Answer the questions guests ask most often.'" icon="question-mark-circle">
            <x-ui.button :href="route('admin.faqs.create')" icon="plus" class="mt-4">Add FAQ</x-ui.button>
        </x-ui.empty-state>
    @else
        <x-ui.card :padded="false">
            <x-admin.sortable :url="route('admin.faqs.reorder')" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="w-28 px-3 py-3"><span class="sr-only">Order</span></th>
                            <th scope="col" class="px-5 py-3">Question</th>
                            <th scope="col" class="px-5 py-3">Status</th>
                            <th scope="col" class="px-5 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($faqs as $faq)
                            <tr data-sortable-id="{{ $faq->id }}" draggable="true">
                                <td class="px-3 py-3"><x-admin.sort-handle :label="$faq->adminLabel()" /></td>
                                <td class="px-5 py-3">
                                    <p class="font-medium text-slate-900">{{ $faq->question }}</p>
                                    <p class="line-clamp-1 text-xs text-slate-500">{{ $faq->answer }}</p>
                                </td>
                                <td class="px-5 py-3">
                                    <x-ui.badge :color="$faq->is_active ? 'garden' : 'slate'">{{ $faq->is_active ? 'Active' : 'Inactive' }}</x-ui.badge>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3">
                                    <div class="flex justify-end gap-1">
                                        <x-ui.button :href="route('admin.faqs.edit', $faq)" variant="secondary" size="sm" icon="pencil-square">Edit<span class="sr-only"> {{ $faq->adminLabel() }}</span></x-ui.button>
                                        <x-admin.confirm-delete :action="route('admin.faqs.destroy', $faq)" :label="$faq->adminLabel()" :name="'delete-faq-'.$faq->id" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-admin.sortable>

            @if ($faqs->hasPages())
                <x-slot:footer>{{ $faqs->links() }}</x-slot:footer>
            @endif
        </x-ui.card>
    @endif
@endsection
