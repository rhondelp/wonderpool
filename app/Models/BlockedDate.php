<?php

namespace App\Models;

use Database\Factories\BlockedDateFactory;
use DateTimeInterface;
use App\Models\Concerns\AdminListable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Admin-blocked window [starts_at, ends_at) during which nothing can be booked.
 *
 * @property int $id
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property string $reason
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class BlockedDate extends Model
{
    use AdminListable;

    /** @use HasFactory<BlockedDateFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'starts_at',
        'ends_at',
        'reason',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * Columns matched by the admin search box (ILIKE).
     *
     * @return list<string>
     */
    public static function adminSearchColumns(): array
    {
        return ['reason'];
    }

    /**
     * Reason and start date, e.g. "Pool maintenance (Dec 5, 2026)".
     */
    public function adminLabel(): string
    {
        return "{$this->reason} ({$this->starts_at->format('M j, Y')})";
    }

    /**
     * Whether the block covers whole days (midnight to midnight).
     */
    public function isWholeDays(): bool
    {
        return $this->starts_at->format('H:i:s') === '00:00:00' && $this->ends_at->format('H:i:s') === '00:00:00';
    }

    /**
     * Admin who created the block.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Blocks overlapping [$start, $end). Touching boundaries are NOT overlaps.
     *
     * @param  Builder<BlockedDate>  $query
     */
    public function scopeOverlapping(Builder $query, DateTimeInterface $start, DateTimeInterface $end): void
    {
        $query->where('starts_at', '<', $end)->where('ends_at', '>', $start);
    }
}
