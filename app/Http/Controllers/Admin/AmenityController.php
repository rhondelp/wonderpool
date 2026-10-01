<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Content\ReorderRequest;
use App\Http\Requests\Admin\Content\StoreAmenityRequest;
use App\Http\Requests\Admin\Content\UpdateAmenityRequest;
use App\Models\Amenity;
use App\Services\Content\AmenityService;
use App\Services\Content\ContentService;
use App\Support\AmenityIcons;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Owner-only amenity management (icon, optional photo, display order).
 */
class AmenityController extends Controller
{
    /**
     * Searchable, filterable, sortable list.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Amenity::class);

        return view('admin.amenities.index', [
            'amenities' => Amenity::query()
                ->search($request->string('q')->toString())
                ->whereState($request->string('status')->toString())
                ->ordered()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    /**
     * New-amenity form.
     */
    public function create(): View
    {
        Gate::authorize('create', Amenity::class);

        return view('admin.amenities.create', ['amenity' => new Amenity(['is_active' => true, 'icon' => 'sparkles']), 'icons' => AmenityIcons::all()]);
    }

    /**
     * Saves a new amenity (and photo).
     */
    public function store(StoreAmenityRequest $request, AmenityService $amenities): RedirectResponse
    {
        $amenity = $amenities->create($request->payload(), $request->image());

        return redirect()->route('admin.amenities.index')->with('success', "{$amenity->name} was added.");
    }

    /**
     * Edit form.
     */
    public function edit(Amenity $amenity): View
    {
        Gate::authorize('update', $amenity);

        return view('admin.amenities.edit', ['amenity' => $amenity, 'icons' => AmenityIcons::all()]);
    }

    /**
     * Saves changes; may replace or remove the photo.
     */
    public function update(UpdateAmenityRequest $request, Amenity $amenity, AmenityService $amenities): RedirectResponse
    {
        $amenities->update($amenity, $request->payload(), $request->image(), $request->boolean('remove_image'));

        return redirect()->route('admin.amenities.index')->with('success', "{$amenity->name} was updated.");
    }

    /**
     * Deletes the amenity and its photo.
     */
    public function destroy(Amenity $amenity, AmenityService $amenities): RedirectResponse
    {
        Gate::authorize('delete', $amenity);
        $amenities->delete($amenity);

        return redirect()->route('admin.amenities.index')->with('success', "{$amenity->name} was deleted.");
    }

    /**
     * Saves a drag-and-drop order (JSON).
     */
    public function reorder(ReorderRequest $request, ContentService $content): JsonResponse
    {
        Gate::authorize('reorder', Amenity::class);
        $content->reorder(Amenity::class, $request->ids());

        return response()->json(['message' => 'Order saved.']);
    }
}
