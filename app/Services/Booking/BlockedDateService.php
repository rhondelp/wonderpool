<?php

namespace App\Services\Booking;

use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\User;
use App\Services\Content\ContentService;
use Illuminate\Database\Eloquent\Collection;

/**
 * Blocked dates (maintenance, private use). Saving a block never cancels bookings; it returns
 * the active bookings that overlap so the admin can contact those guests (D-021).
 */
class BlockedDateService
{
    /**
     * @param  ContentService  $content  Generic CRUD + audit
     * @param  AvailabilityService  $availability  Overlap lookups
     */
    public function __construct(
        private readonly ContentService $content,
        private readonly AvailabilityService $availability,
    ) {
    }

    /**
     * Creates a block.
     *
     * @param  array{starts_at: \DateTimeInterface, ends_at: \DateTimeInterface, reason: string}  $data  Validated payload
     * @param  User  $actor  Admin creating it (stored as created_by)
     * @return array{block: BlockedDate, conflicts: Collection<int, Booking>}
     */
    public function create(array $data, User $actor): array
    {
        $block = $this->content->create(BlockedDate::class, $data + ['created_by' => $actor->id]);

        return ['block' => $block, 'conflicts' => $this->overlappingBookings($block)];
    }

    /**
     * Updates a block.
     *
     * @param  array{starts_at: \DateTimeInterface, ends_at: \DateTimeInterface, reason: string}  $data  Validated payload
     * @return array{block: BlockedDate, conflicts: Collection<int, Booking>}
     */
    public function update(BlockedDate $block, array $data): array
    {
        $this->content->update($block, $data);

        return ['block' => $block, 'conflicts' => $this->overlappingBookings($block)];
    }

    /**
     * Deletes a block (the dates become bookable again).
     */
    public function delete(BlockedDate $block): void
    {
        $this->content->delete($block);
    }

    /**
     * Active bookings inside the block's window.
     *
     * @return Collection<int, Booking>
     */
    public function overlappingBookings(BlockedDate $block): Collection
    {
        return $this->availability->conflicts($block->starts_at, $block->ends_at)['bookings'];
    }
}
