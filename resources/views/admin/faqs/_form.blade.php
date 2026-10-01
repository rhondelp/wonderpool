{{--
    FAQ fields shared by create/edit. To add a field: add it here, to FaqRequest::rules(),
    the faqs migration and Faq::$fillable.

    @param \App\Models\Faq $faq
--}}
<div class="space-y-5">
    <x-ui.input name="question" label="Question" :value="$faq->question" required />
    <x-ui.textarea name="answer" label="Answer" :value="$faq->answer" rows="6" required />
    <x-ui.input name="sort_order" type="number" min="0" label="Display order" :value="$faq->sort_order" hint="Leave blank to place it last. You can also drag rows in the list." />
    <x-ui.checkbox name="is_active" label="Active" :checked="$faq->is_active" hint="Inactive FAQs are hidden from the website." />
</div>
