{{--
    Package fields shared by create/edit. To add a field: add it here, to PackageRequest::rules(),
    the packages migration and Package::$fillable.

    @param \App\Models\Package $package
--}}
<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <x-ui.input name="name" label="Name" :value="$package->name" required />
    </div>
    <x-ui.input name="code" label="Code" :value="$package->code" hint="Short unique code, e.g. DAY-A. Capital letters, numbers, dashes." required />
    <x-ui.input name="base_price" type="number" step="0.01" min="0" label="Base price (₱)" :value="$package->base_price_cents !== null ? number_format(\App\Support\Money::toPesos($package->base_price_cents), 2, '.', '') : null" required />
    <x-ui.input name="start_time" type="time" label="Start time" :value="$package->start_time ? substr($package->start_time, 0, 5) : null" required />
    <x-ui.input name="end_time" type="time" label="End time" :value="$package->end_time ? substr($package->end_time, 0, 5) : null" required />
    <div class="sm:col-span-2">
        <x-ui.checkbox name="crosses_midnight" label="Ends the next day" :checked="$package->crosses_midnight" hint="For overnight and 24-hour packages, e.g. 7:00 PM to 5:00 AM." />
    </div>
    <x-ui.input name="max_pax" type="number" min="1" label="Maximum guests" :value="$package->max_pax" required />
    <x-ui.input name="sort_order" type="number" min="0" label="Display order" :value="$package->sort_order" hint="Leave blank to place it last. You can also drag rows in the list." />
    <div class="sm:col-span-2">
        <x-ui.textarea name="description" label="Description" :value="$package->description" rows="3" />
    </div>
    <div class="sm:col-span-2">
        <x-ui.checkbox name="is_active" label="Active" :checked="$package->is_active" hint="Inactive packages are hidden from guests and cannot be booked." />
    </div>
</div>
