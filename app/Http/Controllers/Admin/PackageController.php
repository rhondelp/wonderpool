<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ContentInUseException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Content\ReorderRequest;
use App\Http\Requests\Admin\Content\StorePackageRequest;
use App\Http\Requests\Admin\Content\UpdatePackageRequest;
use App\Models\Package;
use App\Services\Content\ContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Owner-only package management (Day / Night / 24-Hour and future packages).
 */
class PackageController extends Controller
{
    /**
     * Searchable, filterable, sortable list.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Package::class);

        return view('admin.packages.index', [
            'packages' => Package::query()
                ->withCount('bookings')
                ->search($request->string('q')->toString())
                ->whereState($request->string('status')->toString())
                ->ordered()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    /**
     * New-package form.
     */
    public function create(): View
    {
        Gate::authorize('create', Package::class);

        return view('admin.packages.create', ['package' => new Package(['is_active' => true, 'max_pax' => 50])]);
    }

    /**
     * Saves a new package.
     */
    public function store(StorePackageRequest $request, ContentService $content): RedirectResponse
    {
        $package = $content->create(Package::class, $request->payload());

        return redirect()->route('admin.packages.index')->with('success', "{$package->name} was added.");
    }

    /**
     * Edit form.
     */
    public function edit(Package $package): View
    {
        Gate::authorize('update', $package);

        return view('admin.packages.edit', ['package' => $package]);
    }

    /**
     * Saves changes. Existing bookings keep their own times and price snapshot.
     */
    public function update(UpdatePackageRequest $request, Package $package, ContentService $content): RedirectResponse
    {
        $content->update($package, $request->payload());

        return redirect()->route('admin.packages.index')->with('success', "{$package->name} was updated.");
    }

    /**
     * Deletes a package with no bookings; otherwise explains how to deactivate it.
     */
    public function destroy(Package $package, ContentService $content): RedirectResponse
    {
        Gate::authorize('delete', $package);

        try {
            $content->delete($package);
        } catch (ContentInUseException $e) {
            return back()->with('warning', $e->getMessage());
        }

        return redirect()->route('admin.packages.index')->with('success', "{$package->name} was deleted.");
    }

    /**
     * Saves a drag-and-drop order (JSON).
     */
    public function reorder(ReorderRequest $request, ContentService $content): JsonResponse
    {
        Gate::authorize('reorder', Package::class);
        $content->reorder(Package::class, $request->ids());

        return response()->json(['message' => 'Order saved.']);
    }
}
