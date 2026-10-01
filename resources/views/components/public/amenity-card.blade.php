{{--
    x-public.amenity-card — Amenity with its icon, optional photo (lazy, sized) and description.

    @prop \App\Models\Amenity $amenity  Active amenity (required)

    Usage: <x-public.amenity-card :amenity="$amenity" />
--}}
@props([
    'amenity',
])

<article {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200']) }}>
    @if ($amenity->image_path)
        <img src="{{ $amenity->imageUrl(\App\Enums\ImageVariant::Thumb) }}" alt="{{ $amenity->name }}" width="480" height="320" loading="lazy" decoding="async" class="aspect-[3/2] w-full object-cover">
    @endif
    <div class="p-5">
        <div class="flex items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-pool-100 to-garden-100 text-pool-700">
                <x-dynamic-component :component="'heroicon-o-'.$amenity->icon" class="h-5 w-5" aria-hidden="true" />
            </span>
            <h3 class="font-semibold text-pool-900">{{ $amenity->name }}</h3>
        </div>
        @if ($amenity->description)
            <p class="mt-3 text-sm text-slate-600">{{ $amenity->description }}</p>
        @endif
    </div>
</article>
