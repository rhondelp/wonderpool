<?php

/*
| M5: public pages render content from the database/settings, hide empty sections,
| and expose SEO tags, sitemap.xml and robots.txt.
*/

use App\Models\AddOn;
use App\Models\Amenity;
use App\Models\Faq;
use App\Models\GalleryImage;
use App\Models\Setting;
use App\Services\SettingService;
use Database\Seeders\SettingSeeder;

beforeEach(function () {
    $this->seed(SettingSeeder::class);
});

/**
 * Overrides settings and clears the cache.
 *
 * @param  array<string, string>  $values
 */
function setSettings(array $values): void
{
    foreach ($values as $key => $value) {
        Setting::query()->updateOrCreate(['key' => $key], ['value' => $value, 'group' => strstr($key, '.', true)]);
    }
    app(SettingService::class)->flush();
}

it('renders every public page', function (string $uri) {
    $this->get($uri)->assertOk()->assertSee('Skip to content');
})->with(['/', '/amenities', '/packages', '/gallery', '/faq', '/contact', '/policies', '/book', '/track']);

it('builds the home page from settings and active content only', function () {
    setSettings(['content.hero_title' => 'Splash all day', 'content.highlights' => "Exclusive use\n\nTwo pools"]);
    dayPackage();
    nightPackage(['is_active' => false]);
    Amenity::factory()->create(['name' => 'Infinity Pool']);
    Amenity::factory()->create(['name' => 'Hidden Sauna', 'is_active' => false]);
    GalleryImage::factory()->create(['caption' => 'Sunset swim']);
    GalleryImage::factory()->hidden()->create(['caption' => 'Private shot']);

    $this->get('/')->assertOk()
        ->assertSee('Splash all day')
        ->assertSee('Exclusive use')->assertSee('Two pools')
        ->assertSee('Day Package (A)')->assertSee('₱7,000.00')
        ->assertDontSee('Night Package (D)')
        ->assertSee('Infinity Pool')->assertDontSee('Hidden Sauna')
        ->assertSee('Sunset swim')->assertDontSee('Private shot');
});

it('hides home sections that have no content', function () {
    setSettings(['content.highlights' => '', 'content.about' => '', 'contact.map_embed_url' => '', 'contact.address' => '']);

    $this->get('/')->assertOk()
        ->assertDontSee('All amenities')
        ->assertDontSee('View gallery')
        ->assertDontSee('About the resort')
        ->assertDontSee('How to find us')
        ->assertDontSee('aria-label="Highlights"', false);
});

it('lists packages with add-ons and links to the booking form', function () {
    $day = dayPackage();
    AddOn::factory()->create(['name' => 'Videoke set', 'price_cents' => 50_000]);
    AddOn::factory()->inactive()->create(['name' => 'Old add-on']);

    $this->get('/packages')->assertOk()
        ->assertSee('Day Package (A)')
        ->assertSee(route('book', ['package' => $day->id]), false)
        ->assertSee('Videoke set')->assertSee('₱500.00')
        ->assertDontSee('Old add-on');
});

it('offers gallery filters only for categories that have photos', function () {
    GalleryImage::factory()->create(['category' => 'pools', 'caption' => 'Pool view']);

    $this->get('/gallery')->assertOk()->assertSee('Pool view')->assertDontSee('aria-label="Filter photos"', false);

    GalleryImage::factory()->create(['category' => 'events', 'caption' => 'Debut']);
    $this->get('/gallery')->assertSee('aria-label="Filter photos"', false)->assertSee('Events')->assertDontSee('>Rooms</button>', false);
});

it('shows active FAQs only', function () {
    Faq::factory()->create(['question' => 'Can we bring food?']);
    Faq::factory()->create(['question' => 'Secret question?', 'is_active' => false]);

    $this->get('/faq')->assertSee('Can we bring food?')->assertDontSee('Secret question?');
});

it('renders owner text as safe Markdown on the policies page', function () {
    setSettings(['content.house_rules' => "**No glass** near the pool.\n\n<script>alert(1)</script>\n\n- Rule one\n- Rule two", 'content.cancellation_policy' => '']);

    $this->get('/policies')->assertOk()
        ->assertSee('<strong>No glass</strong>', false)
        ->assertSee('<li>Rule one</li>', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('Cancellation &amp; rescheduling', false);
});

it('shows contact details and the map only when set', function () {
    setSettings(['contact.phone' => '+63 917 000 0000', 'contact.map_embed_url' => 'https://www.google.com/maps/embed?pb=abc']);
    $this->get('/contact')->assertSee('+63 917 000 0000')->assertSee('https://www.google.com/maps/embed?pb=abc', false);

    setSettings(['contact.map_embed_url' => '']);
    $this->get('/contact')->assertDontSee('<iframe', false);
});

it('sets SEO tags from settings with a gallery share image fallback', function () {
    setSettings(['general.resort_name' => 'Wonderpool Test Resort', 'seo.meta_description' => 'Best pool day in town.']);
    GalleryImage::factory()->create(['path' => 'gallery/originals/abc.jpg']);

    $this->get('/amenities')->assertOk()
        ->assertSee('<title>Amenities · Wonderpool Test Resort</title>', false)
        ->assertSee('<meta name="description" content="Best pool day in town.">', false)
        ->assertSee('property="og:image" content="'.url('/storage/gallery/large/abc.webp').'"', false)
        ->assertSee('<link rel="canonical" href="'.url('/amenities').'">', false);
});

it('serves sitemap.xml and robots.txt', function () {
    $this->get('/sitemap.xml')->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('<loc>'.route('packages').'</loc>', false);

    $this->get('/robots.txt')->assertOk()
        ->assertSee('Disallow: /admin')
        ->assertSee('Sitemap: '.route('sitemap'));
});

it('lets the owner edit website content and SEO in Settings', function () {
    $this->actingAs(App\Models\User::factory()->owner()->create())
        ->get('/admin/settings/content')->assertOk()->assertSee('Home headline')->assertSee('House rules');
    $this->get('/admin/settings/seo')->assertOk()->assertSee('Search description');
});
