{{--
    Add-on fields shared by create/edit. To add a field: add it here, to AddOnRequest::rules(),
    the add_ons migration and AddOn::$fillable.

    @param \App\Models\AddOn $addOn
--}}
<div class="space-y-5">
    <x-ui.input name="name" label="Name" :value="$addOn->name" required />
    <x-ui.input name="price" type="number" step="0.01" min="0" label="Price (₱)" :value="$addOn->price_cents !== null ? number_format(\App\Support\Money::toPesos($addOn->price_cents), 2, '.', '') : null" required />
    <x-ui.textarea name="description" label="Description" :value="$addOn->description" rows="3" />
    <x-ui.checkbox name="is_active" label="Active" :checked="$addOn->is_active" hint="Inactive add-ons are not offered to guests." />
</div>
