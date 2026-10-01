<?php

/*
| M3: ContentService::reorder slot algorithm and ImageService path helpers.
*/

use App\Enums\ImageVariant;
use App\Models\Faq;
use App\Services\Content\ContentService;
use App\Services\Content\ImageService;
use Illuminate\Validation\ValidationException;

it('reorders a subset within the slots it occupies and renumbers everything', function () {
    // Full order: a b c d e. Reorder only [d, b] (e.g. a filtered page) → a d c b e.
    [$a, $b, $c, $d, $e] = Faq::factory()->count(5)->sequence(fn ($s) => ['sort_order' => ($s->index + 1) * 10])->create();

    app(ContentService::class)->reorder(Faq::class, [$d->id, $b->id]);

    expect(Faq::query()->ordered()->pluck('id')->all())->toBe([$a->id, $d->id, $c->id, $b->id, $e->id])
        ->and(Faq::query()->ordered()->pluck('sort_order')->all())->toBe([1, 2, 3, 4, 5]);
});

it('repairs duplicate sort orders', function () {
    $faqs = Faq::factory()->count(3)->create(['sort_order' => 0]);

    app(ContentService::class)->reorder(Faq::class, [$faqs[2]->id, $faqs[0]->id, $faqs[1]->id]);

    expect(Faq::query()->ordered()->pluck('sort_order')->all())->toBe([1, 2, 3])
        ->and(Faq::query()->ordered()->pluck('id')->all())->toBe([$faqs[2]->id, $faqs[0]->id, $faqs[1]->id]);
});

it('rejects unknown ids without changing anything', function () {
    $faq = Faq::factory()->create(['sort_order' => 7]);

    expect(fn () => app(ContentService::class)->reorder(Faq::class, [$faq->id, 999999]))->toThrow(ValidationException::class)
        ->and($faq->fresh()->sort_order)->toBe(7);
});

it('derives variant paths from the original path', function () {
    $original = 'gallery/originals/abc-123.png';

    expect(ImageService::variantPath($original, ImageVariant::Original))->toBe($original)
        ->and(ImageService::variantPath($original, ImageVariant::Large))->toBe('gallery/large/abc-123.webp')
        ->and(ImageService::variantPath($original, ImageVariant::Thumb))->toBe('gallery/thumbs/abc-123.webp')
        ->and(ImageService::variantPath('legacy/photo.jpg', ImageVariant::Thumb))->toBe('legacy/photo.jpg')
        ->and(ImageService::url(null))->toBeNull();
});
