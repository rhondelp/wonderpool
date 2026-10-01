<?php

namespace App\Http\Controllers\Public;

use App\Enums\GalleryCategory;
use App\Http\Controllers\Controller;
use App\Services\PublicContentService;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Public content pages. Everything comes from the database/settings via PublicContentService (D-027).
 */
class PageController extends Controller
{
    /**
     * @param  PublicContentService  $content  Active content + settings
     */
    public function __construct(private readonly PublicContentService $content)
    {
    }

    /**
     * Home: hero, highlights, about, amenities, packages, gallery strip, location, CTA.
     */
    public function home(): View
    {
        return view('public.home', [
            'heroTitle' => $this->content->text('content.hero_title'),
            'heroSubtitle' => $this->content->text('content.hero_subtitle'),
            'about' => $this->content->text('content.about'),
            'highlights' => $this->content->highlights(),
            'amenities' => $this->content->amenities(6),
            'packages' => $this->content->packages(),
            'gallery' => $this->content->gallery(8),
        ]);
    }

    /**
     * All active amenities.
     */
    public function amenities(): View
    {
        return view('public.amenities', ['amenities' => $this->content->amenities()]);
    }

    /**
     * Packages & rates, plus add-ons.
     */
    public function packages(): View
    {
        return view('public.packages', [
            'packages' => $this->content->packages(),
            'addOns' => $this->content->addOns(),
        ]);
    }

    /**
     * Gallery with category filter and lightbox.
     */
    public function gallery(): View
    {
        $images = $this->content->gallery();

        return view('public.gallery', [
            'images' => $images,
            'categories' => collect(GalleryCategory::cases())->filter(fn (GalleryCategory $c): bool => $images->contains('category', $c))->values(),
        ]);
    }

    /**
     * Frequently asked questions.
     */
    public function faq(): View
    {
        return view('public.faq', ['faqs' => $this->content->faqs()]);
    }

    /**
     * Contact details and map.
     */
    public function contact(): View
    {
        return view('public.contact');
    }

    /**
     * House rules and cancellation policy.
     */
    public function policies(): View
    {
        return view('public.policies', [
            'houseRules' => $this->content->text('content.house_rules'),
            'cancellationPolicy' => $this->content->text('content.cancellation_policy'),
        ]);
    }

    /**
     * XML sitemap of the public pages.
     */
    public function sitemap(): Response
    {
        $routes = ['home', 'amenities', 'packages', 'gallery', 'faq', 'contact', 'policies', 'book', 'track'];

        return response()
            ->view('public.sitemap', ['urls' => array_map(fn (string $name): string => route($name), $routes)])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * robots.txt pointing to the sitemap; admin and booking-private pages are disallowed.
     */
    public function robots(): Response
    {
        $lines = ['User-agent: *', 'Disallow: /admin', 'Disallow: /book/WP-', 'Disallow: /track/', '', 'Sitemap: '.route('sitemap')];

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
