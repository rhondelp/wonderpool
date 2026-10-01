{{--
    Amenity fields shared by create/edit. To add a field: add it here, to AmenityRequest::rules(),
    the amenities migration and Amenity::$fillable. Icons come from App\Support\AmenityIcons.

    @param \App\Models\Amenity $amenity
    @param array<string, string> $icons
--}}
<div class="space-y-5">
    <x-ui.input name="name" label="Name" :value="$amenity->name" required />
    <x-ui.textarea name="description" label="Description" :value="$amenity->description" rows="3" />
    <x-admin.icon-picker :icons="$icons" :selected="$amenity->icon" />

    <div class="space-y-3">
        @if ($amenity->image_path)
            <div class="flex items-center gap-4">
                <img src="{{ $amenity->imageUrl(\App\Enums\ImageVariant::Thumb) }}" alt="Current photo of {{ $amenity->name }}" class="h-20 w-28 rounded-lg object-cover ring-1 ring-slate-200">
                <x-ui.checkbox name="remove_image" label="Remove current photo" />
            </div>
        @endif
        <x-ui.file-input name="image" :label="$amenity->image_path ? 'Replace photo' : 'Photo (optional)'" hint="JPG, PNG or WebP, up to 5 MB." />
    </div>

    <x-ui.input name="sort_order" type="number" min="0" label="Display order" :value="$amenity->sort_order" hint="Leave blank to place it last. You can also drag rows in the list." />
    <x-ui.checkbox name="is_active" label="Active" :checked="$amenity->is_active" hint="Inactive amenities are hidden from the website." />
</div>
