<?php

/*
| M3: amenity CRUD with curated icons and optional photo (stored + resized on the public disk).
*/

use App\Enums\ActivityAction;
use App\Enums\ImageVariant;
use App\Models\ActivityLog;
use App\Models\Amenity;
use App\Models\User;
use App\Services\Content\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->owner()->create());
});

/**
 * Asserts every stored version of an image exists (or not).
 */
function assertImageFiles(string $original, bool $exist = true): void
{
    foreach (ImageVariant::cases() as $variant) {
        $path = ImageService::variantPath($original, $variant);
        $exist ? Storage::disk('public')->assertExists($path) : Storage::disk('public')->assertMissing($path);
    }
}

it('lists amenities with search', function () {
    Amenity::factory()->create(['name' => 'Kiddie Pool']);
    Amenity::factory()->create(['name' => 'Function Hall']);

    $this->get('/admin/amenities?q=kiddie')->assertOk()->assertSee('Kiddie Pool')->assertDontSee('Function Hall');
});

it('creates an amenity with a photo and resized versions', function () {
    $this->post('/admin/amenities', [
        'name' => 'Videoke Room',
        'description' => 'Sing your heart out.',
        'icon' => 'microphone',
        'image' => UploadedFile::fake()->image('room.jpg', 2400, 1600),
        'is_active' => '1',
    ])->assertRedirect('/admin/amenities')->assertSessionHasNoErrors();

    $amenity = Amenity::query()->where('name', 'Videoke Room')->sole();
    expect($amenity->image_path)->toStartWith('amenities/originals/')->toEndWith('.jpg');
    assertImageFiles($amenity->image_path);

    $thumb = getimagesizefromstring(Storage::disk('public')->get(ImageService::variantPath($amenity->image_path, ImageVariant::Thumb)));
    $large = getimagesizefromstring(Storage::disk('public')->get(ImageService::variantPath($amenity->image_path, ImageVariant::Large)));
    expect([$thumb[0], $thumb[1], $thumb['mime']])->toBe([480, 320, 'image/webp'])
        ->and([$large[0], $large[1]])->toBe([1600, 1067]);
});

it('creates an amenity without a photo', function () {
    $this->post('/admin/amenities', ['name' => 'Parking', 'icon' => 'truck', 'is_active' => '1'])->assertSessionHasNoErrors();

    expect(Amenity::query()->where('name', 'Parking')->sole()->image_path)->toBeNull();
});

it('validates amenity input and images', function (array $input, string $field) {
    $this->post('/admin/amenities', $input + ['name' => 'Pool', 'icon' => 'sun'])->assertSessionHasErrors($field);
})->with([
    'icon outside the curated list' => fn () => [['icon' => 'academic-cap'], 'icon'],
    'missing icon' => fn () => [['icon' => ''], 'icon'],
    'pdf instead of image' => fn () => [['image' => UploadedFile::fake()->create('menu.pdf', 100, 'application/pdf')], 'image'],
    'gif not allowed' => fn () => [['image' => UploadedFile::fake()->image('a.gif')], 'image'],
    'larger than 5 MB' => fn () => [['image' => UploadedFile::fake()->image('big.jpg')->size(5121)], 'image'],
]);

it('replaces the photo and deletes the old files', function () {
    $this->post('/admin/amenities', ['name' => 'Pool', 'icon' => 'sun', 'image' => UploadedFile::fake()->image('a.png', 800, 600)]);
    $amenity = Amenity::query()->sole();
    $old = $amenity->image_path;

    $this->put("/admin/amenities/{$amenity->id}", ['name' => 'Pool', 'icon' => 'sun', 'image' => UploadedFile::fake()->image('b.webp', 800, 600), 'is_active' => '1'])
        ->assertSessionHasNoErrors();

    $amenity->refresh();
    expect($amenity->image_path)->not->toBe($old)->toEndWith('.webp');
    assertImageFiles($amenity->image_path);
    assertImageFiles($old, exist: false);
});

it('removes the photo when asked', function () {
    $this->post('/admin/amenities', ['name' => 'Pool', 'icon' => 'sun', 'image' => UploadedFile::fake()->image('a.jpg', 800, 600)]);
    $amenity = Amenity::query()->sole();
    $old = $amenity->image_path;

    $this->put("/admin/amenities/{$amenity->id}", ['name' => 'Pool', 'icon' => 'sun', 'remove_image' => '1', 'is_active' => '1']);

    expect($amenity->fresh()->image_path)->toBeNull();
    assertImageFiles($old, exist: false);
});

it('deletes an amenity with its photo files and logs it', function () {
    $this->post('/admin/amenities', ['name' => 'Pool', 'icon' => 'sun', 'image' => UploadedFile::fake()->image('a.jpg', 800, 600)]);
    $amenity = Amenity::query()->sole();

    $this->delete("/admin/amenities/{$amenity->id}")->assertRedirect('/admin/amenities');

    expect(Amenity::query()->count())->toBe(0)
        ->and(ActivityLog::query()->where('action', ActivityAction::ContentDeleted->value)->value('properties'))->toMatchArray(['type' => 'Amenity', 'label' => 'Pool']);
    assertImageFiles($amenity->image_path, exist: false);
});

it('reorders amenities', function () {
    [$a, $b] = Amenity::factory()->count(2)->sequence(['sort_order' => 1], ['sort_order' => 2])->create();

    $this->patchJson('/admin/amenities/reorder', ['ids' => [$b->id, $a->id]])->assertOk();

    expect(Amenity::query()->ordered()->pluck('id')->all())->toBe([$b->id, $a->id]);
});

it('renders the icon picker on the forms', function () {
    $amenity = Amenity::factory()->create(['icon' => 'trophy']);

    $this->get('/admin/amenities/create')->assertOk()->assertSee('Search icons');
    $this->get("/admin/amenities/{$amenity->id}/edit")->assertOk()->assertSee('value="trophy" class="sr-only" checked', false);
});
