<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Content\ReorderRequest;
use App\Http\Requests\Admin\Content\StoreFaqRequest;
use App\Http\Requests\Admin\Content\UpdateFaqRequest;
use App\Models\Faq;
use App\Services\Content\ContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Owner-only FAQ management with drag-and-drop order.
 */
class FaqController extends Controller
{
    /**
     * Searchable, filterable, sortable list.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Faq::class);

        return view('admin.faqs.index', [
            'faqs' => Faq::query()
                ->search($request->string('q')->toString())
                ->whereState($request->string('status')->toString())
                ->ordered()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    /**
     * New-FAQ form.
     */
    public function create(): View
    {
        Gate::authorize('create', Faq::class);

        return view('admin.faqs.create', ['faq' => new Faq(['is_active' => true])]);
    }

    /**
     * Saves a new FAQ.
     */
    public function store(StoreFaqRequest $request, ContentService $content): RedirectResponse
    {
        $content->create(Faq::class, $request->payload());

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ added.');
    }

    /**
     * Edit form.
     */
    public function edit(Faq $faq): View
    {
        Gate::authorize('update', $faq);

        return view('admin.faqs.edit', ['faq' => $faq]);
    }

    /**
     * Saves changes.
     */
    public function update(UpdateFaqRequest $request, Faq $faq, ContentService $content): RedirectResponse
    {
        $content->update($faq, $request->payload());

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ updated.');
    }

    /**
     * Deletes the FAQ.
     */
    public function destroy(Faq $faq, ContentService $content): RedirectResponse
    {
        Gate::authorize('delete', $faq);
        $content->delete($faq);

        return redirect()->route('admin.faqs.index')->with('success', 'FAQ deleted.');
    }

    /**
     * Saves a drag-and-drop order (JSON).
     */
    public function reorder(ReorderRequest $request, ContentService $content): JsonResponse
    {
        Gate::authorize('reorder', Faq::class);
        $content->reorder(Faq::class, $request->ids());

        return response()->json(['message' => 'Order saved.']);
    }
}
