<?php

/*
| M3: content modules are owner-only (staff = bookings only) and listed in the owner's nav.
*/

use App\Models\AddOn;
use App\Models\Amenity;
use App\Models\Faq;
use App\Models\GalleryImage;
use App\Models\Package;
use App\Models\User;

it('blocks staff from every content route', function (string $method, string $uri) {
    $ids = [
        '{package}' => Package::factory()->create()->id,
        '{add_on}' => AddOn::factory()->create()->id,
        '{amenity}' => Amenity::factory()->create()->id,
        '{gallery}' => GalleryImage::factory()->create()->id,
        '{faq}' => Faq::factory()->create()->id,
    ];

    $this->actingAs(User::factory()->create())
        ->call($method, strtr($uri, $ids))
        ->assertForbidden();
})->with([
    ['GET', '/admin/packages'], ['GET', '/admin/packages/create'], ['POST', '/admin/packages'],
    ['GET', '/admin/packages/{package}/edit'], ['PUT', '/admin/packages/{package}'], ['DELETE', '/admin/packages/{package}'],
    ['PATCH', '/admin/packages/reorder'],
    ['GET', '/admin/add-ons'], ['POST', '/admin/add-ons'], ['DELETE', '/admin/add-ons/{add_on}'],
    ['GET', '/admin/amenities'], ['POST', '/admin/amenities'], ['PUT', '/admin/amenities/{amenity}'], ['PATCH', '/admin/amenities/reorder'],
    ['GET', '/admin/gallery'], ['POST', '/admin/gallery'], ['PATCH', '/admin/gallery/{gallery}/visibility'], ['PATCH', '/admin/gallery/reorder'],
    ['GET', '/admin/faqs'], ['POST', '/admin/faqs'], ['DELETE', '/admin/faqs/{faq}'], ['PATCH', '/admin/faqs/reorder'],
]);

it('redirects guests to the login page', function () {
    $this->get('/admin/packages')->assertRedirect('/admin/login');
});

it('shows content links to owners only', function () {
    $links = ['/admin/packages', '/admin/add-ons', '/admin/amenities', '/admin/gallery', '/admin/faqs'];

    $owner = $this->actingAs(User::factory()->owner()->create())->get('/admin');
    foreach ($links as $link) {
        $owner->assertSee(url($link));
    }

    $staff = $this->actingAs(User::factory()->create())->get('/admin');
    foreach ($links as $link) {
        $staff->assertDontSee(url($link));
    }
});
