<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Models\ActivityLog;
use App\Models\AddOn;
use App\Models\Amenity;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Faq;
use App\Models\GalleryImage;
use App\Models\Package;
use App\Models\Payment;
use App\Models\PricingRule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Read side of the audit trail for the owner's activity log screen (D-036).
 * Entries are append-only; this class never edits or deletes them.
 */
class ActivityLogService
{
    /** Filter value for entries without a user (guests, scheduled jobs). */
    public const SYSTEM_ACTOR = 'system';

    /**
     * Newest-first, filtered, paginated entries with actor and subject loaded.
     *
     * @param  array{user?: string|null, module?: string|null, action?: string|null, from?: string|null, to?: string|null, q?: string|null}  $filters
     * @return LengthAwarePaginator<int, ActivityLog>
     */
    public function listing(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        $user = $filters['user'] ?? null;
        $term = trim((string) ($filters['q'] ?? ''));

        return ActivityLog::query()
            ->with(['user:id,name', 'subject' => function (Relation $morph): void {
                if (! $morph instanceof MorphTo) {
                    return;
                }
                $morph->morphWith([Payment::class => ['booking:id,reference_code']]);
            }])
            ->when($user === self::SYSTEM_ACTOR, fn (Builder $q) => $q->whereNull('user_id'))
            ->when($user !== null && $user !== self::SYSTEM_ACTOR, fn (Builder $q) => $q->where('user_id', (int) $user))
            ->when($filters['action'] ?? null, fn (Builder $q, string $action) => $q->where('action', $action))
            ->when(! ($filters['action'] ?? null) && ($filters['module'] ?? null), fn (Builder $q) => $q->where('action', 'like', $filters['module'].'.%'))
            ->when($filters['from'] ?? null, fn (Builder $q, string $from) => $q->where('created_at', '>=', CarbonImmutable::parse($from)->startOfDay()))
            ->when($filters['to'] ?? null, fn (Builder $q, string $to) => $q->where('created_at', '<', CarbonImmutable::parse($to)->startOfDay()->addDay()))
            ->when($term !== '', function (Builder $q) use ($term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->where(function (Builder $q) use ($like): void {
                    $q->whereRaw('properties::text ILIKE ?', [$like])
                        ->orWhereHasMorph('subject', [Booking::class], fn (Builder $b) => $b->withTrashed()->where('reference_code', 'ILIKE', $like));
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Readable name of the entry's subject, e.g. a booking reference or a package name.
     */
    public function subjectLabel(ActivityLog $log): ?string
    {
        if ($log->subject_type === null) {
            return null;
        }

        $subject = $log->subject;
        $type = class_basename($log->subject_type);

        return match (true) {
            $subject instanceof Booking => $subject->reference_code,
            $subject instanceof Payment => 'Payment #'.$subject->id.($subject->booking ? ' · '.$subject->booking->reference_code : ''),
            $subject instanceof User => $subject->name,
            $subject instanceof Model && method_exists($subject, 'adminLabel') => $subject->adminLabel(),
            default => "{$type} #{$log->subject_id}".($subject === null ? ' (deleted)' : ''),
        };
    }

    /**
     * Admin page for the subject, when one exists.
     */
    public function subjectUrl(ActivityLog $log): ?string
    {
        $subject = $log->subject;

        return match (true) {
            $subject instanceof Booking => route('admin.bookings.show', $subject),
            $subject instanceof Payment => route('admin.bookings.show', $subject->booking_id),
            $subject instanceof User => route('admin.users.edit', $subject),
            $subject instanceof Package => route('admin.packages.edit', $subject),
            $subject instanceof AddOn => route('admin.add-ons.edit', $subject),
            $subject instanceof Amenity => route('admin.amenities.edit', $subject),
            $subject instanceof GalleryImage => route('admin.gallery.edit', $subject),
            $subject instanceof Faq => route('admin.faqs.edit', $subject),
            $subject instanceof BlockedDate => route('admin.blocked-dates.edit', $subject),
            $subject instanceof PricingRule => route('admin.pricing-rules.edit', $subject),
            default => null,
        };
    }

    /**
     * Flattens the stored properties into "Key → value" lines for display (nested values as JSON).
     *
     * @return array<string, string>
     */
    public function details(ActivityLog $log): array
    {
        $lines = [];
        foreach ($log->properties ?? [] as $key => $value) {
            $lines[ucfirst(str_replace('_', ' ', (string) $key))] = match (true) {
                is_bool($value) => $value ? 'yes' : 'no',
                $value === null => '—',
                is_scalar($value) => (string) $value,
                default => (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            };
        }

        return $lines;
    }

    /**
     * Label for a stored action string (falls back to the raw value for retired actions).
     */
    public function actionLabel(string $action): string
    {
        return ActivityAction::tryFrom($action)?->label() ?? $action;
    }

    /**
     * Users who appear as actors, for the filter (includes disabled users).
     *
     * @return array<string, string>
     */
    public function actorOptions(): array
    {
        return [self::SYSTEM_ACTOR => 'Guest / system']
            + User::query()->orderBy('name')->pluck('name', 'id')->mapWithKeys(fn ($name, $id): array => [(string) $id => $name])->all();
    }
}
