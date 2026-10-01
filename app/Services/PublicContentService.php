<?php

namespace App\Services;

use App\Enums\ImageVariant;
use App\Models\AddOn;
use App\Models\Amenity;
use App\Models\Faq;
use App\Models\GalleryImage;
use App\Models\Package;
use Illuminate\Database\Eloquent\Collection;

/**
 * Read-only content for the public site (D-027): only active/visible records in admin order,
 * plus the site-wide settings used by the layout, SEO tags and footer.
 * Empty values are returned as null/empty so views can hide whole sections.
 */
class PublicContentService
{
    /** Highlights shown on the home page. */
    public const MAX_HIGHLIGHTS = 6;

    /**
     * @param  SettingService  $settings  Cached settings
     */
    public function __construct(private readonly SettingService $settings)
    {
    }

    /**
     * Site-wide values for the layout (name, tagline, contact, socials, SEO defaults).
     *
     * @return array{name: string, tagline: ?string, phone: ?string, email: ?string, address: ?string, map_embed_url: ?string, facebook_url: ?string, instagram_url: ?string, meta_description: ?string, og_image_url: ?string}
     */
    public function site(): array
    {
        return [
            'name' => $this->text('general.resort_name') ?? (string) config('app.name'),
            'tagline' => $this->text('content.tagline'),
            'phone' => $this->text('contact.phone'),
            'email' => $this->text('contact.email'),
            'address' => $this->text('contact.address'),
            'map_embed_url' => $this->text('contact.map_embed_url'),
            'facebook_url' => $this->text('social.facebook_url'),
            'instagram_url' => $this->text('social.instagram_url'),
            'meta_description' => $this->text('seo.meta_description'),
            'og_image_url' => $this->text('seo.og_image_url') ?? $this->firstGalleryImageUrl(),
        ];
    }

    /**
     * Setting value trimmed, or null when blank.
     */
    public function text(string $key): ?string
    {
        $value = trim((string) $this->settings->get($key));

        return $value === '' ? null : $value;
    }

    /**
     * Non-empty lines of content.highlights (max MAX_HIGHLIGHTS).
     *
     * @return list<string>
     */
    public function highlights(): array
    {
        $lines = preg_split('/\R/', (string) $this->text('content.highlights')) ?: [];
        $lines = array_values(array_filter(array_map('trim', $lines), fn (string $line): bool => $line !== ''));

        return array_slice($lines, 0, self::MAX_HIGHLIGHTS);
    }

    /**
     * Active packages in display order.
     *
     * @return Collection<int, Package>
     */
    public function packages(): Collection
    {
        return Package::query()->active()->ordered()->get();
    }

    /**
     * Active add-ons by name.
     *
     * @return Collection<int, AddOn>
     */
    public function addOns(): Collection
    {
        return AddOn::query()->active()->orderBy('name')->get();
    }

    /**
     * Active amenities in display order.
     *
     * @param  int|null  $limit  Max rows (home page preview)
     * @return Collection<int, Amenity>
     */
    public function amenities(?int $limit = null): Collection
    {
        return Amenity::query()->active()->ordered()->when($limit, fn ($q) => $q->limit($limit))->get();
    }

    /**
     * Visible gallery images in display order.
     *
     * @param  int|null  $limit  Max rows (home page strip)
     * @return Collection<int, GalleryImage>
     */
    public function gallery(?int $limit = null): Collection
    {
        return GalleryImage::query()->visible()->ordered()->when($limit, fn ($q) => $q->limit($limit))->get();
    }

    /**
     * Active FAQs in display order.
     *
     * @return Collection<int, Faq>
     */
    public function faqs(): Collection
    {
        return Faq::query()->active()->ordered()->get();
    }

    /**
     * Large version of the first visible gallery image (share image fallback).
     */
    private function firstGalleryImageUrl(): ?string
    {
        return GalleryImage::query()->visible()->ordered()->first()?->imageUrl(ImageVariant::Large);
    }
}
