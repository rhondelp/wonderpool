<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GalleryCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Content\ReorderRequest;
use App\Http\Requests\Admin\Content\StoreGalleryImagesRequest;
use App\Http\Requests\Admin\Content\UpdateGalleryImageRequest;
use App\Models\GalleryImage;
use App\Services\Content\ContentService;
use App\Services\Content\GalleryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Owner-only gallery: multi-upload, categories, captions, visibility and drag-and-drop order.
 */
class GalleryController extends Controller
{
    /**
     * Image grid with caption search, category and visibility filters.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', GalleryImage::class);

        $category = GalleryCategory::tryFrom($request->string('category')->toString());

        return view('admin.gallery.index', [
            'images' => GalleryImage::query()
                ->search($request->string('q')->toString())
                ->whereState($request->string('status')->toString())
                ->when($category, fn ($query) => $query->where('category', $category))
                ->ordered()
                ->paginate(24)
                ->withQueryString(),
            'categories' => GalleryCategory::cases(),
        ]);
    }

    /**
     * Upload form.
     */
    public function create(): View
    {
        Gate::authorize('create', GalleryImage::class);

        return view('admin.gallery.create', ['categories' => GalleryCategory::cases()]);
    }

    /**
     * Stores every uploaded image.
     */
    public function store(StoreGalleryImagesRequest $request, GalleryService $gallery): RedirectResponse
    {
        $created = $gallery->upload(
            $request->images(),
            GalleryCategory::from($request->string('category')->toString()),
            $request->filled('caption') ? $request->string('caption')->trim()->toString() : null,
            $request->boolean('is_visible'),
        );

        return redirect()->route('admin.gallery.index')->with('success', count($created).' image(s) uploaded.');
    }

    /**
     * Edit caption, category and visibility.
     */
    public function edit(GalleryImage $gallery): View
    {
        Gate::authorize('update', $gallery);

        return view('admin.gallery.edit', ['image' => $gallery, 'categories' => GalleryCategory::cases()]);
    }

    /**
     * Saves changes.
     */
    public function update(UpdateGalleryImageRequest $request, GalleryImage $gallery, GalleryService $service): RedirectResponse
    {
        $service->update($gallery, $request->validated());

        return redirect()->route('admin.gallery.index')->with('success', 'Image updated.');
    }

    /**
     * Quick show/hide toggle from the grid.
     */
    public function toggleVisibility(GalleryImage $gallery, GalleryService $service): RedirectResponse
    {
        Gate::authorize('update', $gallery);
        $service->toggleVisibility($gallery);

        return back()->with('success', $gallery->is_visible ? 'Image is now visible on the website.' : 'Image is now hidden from the website.');
    }

    /**
     * Deletes the image and its files.
     */
    public function destroy(GalleryImage $gallery, GalleryService $service): RedirectResponse
    {
        Gate::authorize('delete', $gallery);
        $service->delete($gallery);

        return redirect()->route('admin.gallery.index')->with('success', 'Image deleted.');
    }

    /**
     * Saves a drag-and-drop order (JSON).
     */
    public function reorder(ReorderRequest $request, ContentService $content): JsonResponse
    {
        Gate::authorize('reorder', GalleryImage::class);
        $content->reorder(GalleryImage::class, $request->ids());

        return response()->json(['message' => 'Order saved.']);
    }
}
