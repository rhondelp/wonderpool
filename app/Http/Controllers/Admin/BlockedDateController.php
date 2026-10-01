<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Content\StoreBlockedDateRequest;
use App\Http\Requests\Admin\Content\UpdateBlockedDateRequest;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\User;
use App\Services\Booking\BlockedDateService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Owner-only blocked dates (maintenance, private use) that make the resort unbookable.
 */
class BlockedDateController extends Controller
{
    /**
     * List with reason search and upcoming/past filter (default: upcoming).
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', BlockedDate::class);
        $period = $request->string('status')->toString();

        return view('admin.blocked-dates.index', [
            'blocks' => BlockedDate::query()
                ->with('creator')
                ->search($request->string('q')->toString())
                ->when($period === 'past', fn ($q) => $q->where('ends_at', '<=', now())->orderByDesc('starts_at'))
                ->when($period !== 'past' && $period !== 'all', fn ($q) => $q->where('ends_at', '>', now()))
                ->orderBy('starts_at')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    /**
     * New-block form.
     */
    public function create(): View
    {
        Gate::authorize('create', BlockedDate::class);

        return view('admin.blocked-dates.create', ['block' => new BlockedDate()]);
    }

    /**
     * Saves a block and warns about overlapping bookings.
     */
    public function store(StoreBlockedDateRequest $request, BlockedDateService $blocks): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $result = $blocks->create($request->payload(), $user);

        return $this->redirectWithConflicts('Blocked dates added.', $result['conflicts']);
    }

    /**
     * Edit form.
     */
    public function edit(BlockedDate $blockedDate): View
    {
        Gate::authorize('update', $blockedDate);

        return view('admin.blocked-dates.edit', ['block' => $blockedDate]);
    }

    /**
     * Saves changes and warns about overlapping bookings.
     */
    public function update(UpdateBlockedDateRequest $request, BlockedDate $blockedDate, BlockedDateService $blocks): RedirectResponse
    {
        $result = $blocks->update($blockedDate, $request->payload());

        return $this->redirectWithConflicts('Blocked dates updated.', $result['conflicts']);
    }

    /**
     * Removes the block.
     */
    public function destroy(BlockedDate $blockedDate, BlockedDateService $blocks): RedirectResponse
    {
        Gate::authorize('delete', $blockedDate);
        $blocks->delete($blockedDate);

        return redirect()->route('admin.blocked-dates.index')->with('success', 'Blocked dates removed. Those dates can be booked again.');
    }

    /**
     * Success flash plus a warning naming the bookings that overlap the block.
     *
     * @param  Collection<int, Booking>  $conflicts
     */
    private function redirectWithConflicts(string $message, Collection $conflicts): RedirectResponse
    {
        $redirect = redirect()->route('admin.blocked-dates.index')->with('success', $message);

        if ($conflicts->isNotEmpty()) {
            $redirect->with('warning', $conflicts->count().' existing booking(s) overlap these dates and were NOT cancelled: '
                .$conflicts->map(fn (Booking $b): string => $b->reference_code.' ('.$b->starts_at->format('M j, g:i A').')')->implode(', ')
                .'. Contact the guests or reschedule them.');
        }

        return $redirect;
    }
}
