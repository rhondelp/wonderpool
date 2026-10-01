<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\ContentInUseException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Content\StoreAddOnRequest;
use App\Http\Requests\Admin\Content\UpdateAddOnRequest;
use App\Models\AddOn;
use App\Services\Content\ContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Owner-only add-on management (extra hours, extra pax, videoke...). Add-ons are listed by name (no manual order).
 */
class AddOnController extends Controller
{
    /**
     * Searchable, filterable list.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', AddOn::class);

        return view('admin.add-ons.index', [
            'addOns' => AddOn::query()
                ->search($request->string('q')->toString())
                ->whereState($request->string('status')->toString())
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    /**
     * New add-on form.
     */
    public function create(): View
    {
        Gate::authorize('create', AddOn::class);

        return view('admin.add-ons.create', ['addOn' => new AddOn(['is_active' => true])]);
    }

    /**
     * Saves a new add-on.
     */
    public function store(StoreAddOnRequest $request, ContentService $content): RedirectResponse
    {
        $addOn = $content->create(AddOn::class, $request->payload());

        return redirect()->route('admin.add-ons.index')->with('success', "{$addOn->name} was added.");
    }

    /**
     * Edit form.
     */
    public function edit(AddOn $addOn): View
    {
        Gate::authorize('update', $addOn);

        return view('admin.add-ons.edit', ['addOn' => $addOn]);
    }

    /**
     * Saves changes. Existing bookings keep their unit price snapshot.
     */
    public function update(UpdateAddOnRequest $request, AddOn $addOn, ContentService $content): RedirectResponse
    {
        $content->update($addOn, $request->payload());

        return redirect()->route('admin.add-ons.index')->with('success', "{$addOn->name} was updated.");
    }

    /**
     * Deletes an unused add-on; otherwise explains how to deactivate it.
     */
    public function destroy(AddOn $addOn, ContentService $content): RedirectResponse
    {
        Gate::authorize('delete', $addOn);

        try {
            $content->delete($addOn);
        } catch (ContentInUseException $e) {
            return back()->with('warning', $e->getMessage());
        }

        return redirect()->route('admin.add-ons.index')->with('success', "{$addOn->name} was deleted.");
    }
}
