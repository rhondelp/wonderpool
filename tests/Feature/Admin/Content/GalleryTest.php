<?php

/*
| M3: gallery multi-upload, edit, visibility toggle, filters, delete with files, reorder.
*/

use App\Enums\GalleryCategory;
use App\Enums\ImageVariant;
use App\Models\GalleryImage;
use App\Models\User;
use App\Services\Content\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->owner()->create());
});

it('uploads several images at once, in order, with derived versions', function () {
    GalleryImage::factory()->create(['sort_order' => 4]);

    $this->post('/admin/gallery', [
        'images' => [
            UploadedFile::fake()->image('one.jpg', 1200, 800),
            UploadedFile::fake()->image('two.png', 640, 480),
            UploadedFile::fake()->image('three.webp', 300, 300),
        ],
        'category' => 'pools',
        'caption' => 'Summer 2026',
        'is_visible' => '1',
    ])->assertRedirect('/admin/gallery')->assertSessionHas('success', '3 image(s) uploaded.');

    $uploaded = GalleryImage::query()->where('caption', 'Summer 2026')->orderBy('sort_order')->get();
    expect($uploaded)->toHaveCount(3)
        ->and($uploaded->pluck('sort_order')->all())->toBe([5, 6, 7])
        ->and($uploaded->every(fn (GalleryImage $i): bool => $i->category === GalleryCategory::Pools && $i->is_visible))->toBeTrue();

    foreach ($uploaded as $image) {
        expect($image->path)->toStartWith('gallery/originals/');
        foreach (ImageVariant::cases() as $variant) {
            Storage::disk('public')->assertExists(ImageService::variantPath($image->path, $variant));
        }
    }
});

it('validates uploads per file', function (array $input, string $field) {
    $this->post('/admin/gallery', $input + ['category' => 'pools'])->assertSessionHasErrors($field);
})->with([
    'no files' => fn () => [['images' => []], 'images'],
    'one bad file' => fn () => [['images' => [UploadedFile::fake()->image('ok.jpg'), UploadedFile::fake()->create('virus.exe', 10)]], 'images.1'],
    'too large' => fn () => [['images' => [UploadedFile::fake()->image('big.jpg')->size(6000)]], 'images.0'],
    'unknown category' => fn () => [['images' => [UploadedFile::fake()->image('ok.jpg')], 'category' => 'beach'], 'category'],
    'too many files' => fn () => [['images' => array_map(fn ($i) => UploadedFile::fake()->image("{$i}.jpg", 10, 10), range(1, 21))], 'images'],
]);

it('shows per-file errors on the upload form', function () {
    $this->from('/admin/gallery/create')->followingRedirects()
        ->post('/admin/gallery', ['images' => [UploadedFile::fake()->create('notes.txt', 1)], 'category' => 'pools'])
        ->assertSee('The image field must be an image.');
});

it('filters by category, visibility and caption', function () {
    GalleryImage::factory()->create(['category' => 'rooms', 'caption' => 'Deluxe room']);
    GalleryImage::factory()->hidden()->create(['category' => 'events', 'caption' => 'Debut party']);

    $this->get('/admin/gallery?category=rooms')->assertSee('Deluxe room')->assertDontSee('Debut party');
    $this->get('/admin/gallery?status=inactive')->assertSee('Debut party')->assertDontSee('Deluxe room');
    $this->get('/admin/gallery?q=DEBUT')->assertSee('Debut party')->assertDontSee('Deluxe room');
});

it('edits caption, category and visibility', function () {
    $image = GalleryImage::factory()->create(['category' => 'pools']);

    $this->put("/admin/gallery/{$image->id}", ['caption' => 'Night swim', 'category' => 'events', 'is_visible' => '0'])
        ->assertRedirect('/admin/gallery');

    $image->refresh();
    expect($image->caption)->toBe('Night swim')
        ->and($image->category)->toBe(GalleryCategory::Events)
        ->and($image->is_visible)->toBeFalse();
});

it('toggles visibility from the grid', function () {
    $image = GalleryImage::factory()->create(['is_visible' => true]);

    $this->from('/admin/gallery')->patch("/admin/gallery/{$image->id}/visibility")->assertRedirect('/admin/gallery');
    expect($image->fresh()->is_visible)->toBeFalse();

    $this->patch("/admin/gallery/{$image->id}/visibility");
    expect($image->fresh()->is_visible)->toBeTrue();
});

it('deletes the image and every stored version', function () {
    $this->post('/admin/gallery', ['images' => [UploadedFile::fake()->image('a.jpg', 500, 500)], 'category' => 'hall', 'is_visible' => '1']);
    $image = GalleryImage::query()->sole();

    $this->delete("/admin/gallery/{$image->id}")->assertRedirect('/admin/gallery');

    expect(GalleryImage::query()->count())->toBe(0);
    foreach (ImageVariant::cases() as $variant) {
        Storage::disk('public')->assertMissing(ImageService::variantPath($image->path, $variant));
    }
});

it('reorders gallery images', function () {
    [$a, $b, $c] = GalleryImage::factory()->count(3)->sequence(['sort_order' => 1], ['sort_order' => 2], ['sort_order' => 3])->create();

    $this->patchJson('/admin/gallery/reorder', ['ids' => [$b->id, $c->id, $a->id]])->assertOk();

    expect(GalleryImage::query()->ordered()->pluck('id')->all())->toBe([$b->id, $c->id, $a->id]);
});

it('renders the grid, upload and edit pages', function () {
    $image = GalleryImage::factory()->create(['caption' => 'Garden at dusk']);

    $this->get('/admin/gallery')->assertOk()->assertSee('Garden at dusk')->assertSee('data-sortable-id="'.$image->id.'"', false);
    $this->get('/admin/gallery/create')->assertOk()->assertSee('multiple', false);
    $this->get("/admin/gallery/{$image->id}/edit")->assertOk()->assertSee('Garden at dusk');
});
