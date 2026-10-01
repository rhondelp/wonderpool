<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Shared admin index helpers: case-insensitive search (PostgreSQL ILIKE) and an
 * active/inactive filter. Models list their searchable columns in adminSearchColumns()
 * and may override adminStateColumn() (default "is_active") and adminLabel().
 */
trait AdminListable
{
    /**
     * Columns matched by the admin search box.
     *
     * @return list<string>
     */
    abstract public static function adminSearchColumns(): array;

    /**
     * Boolean column behind the active/inactive filter.
     */
    public static function adminStateColumn(): string
    {
        return 'is_active';
    }

    /**
     * Short human label used in flashes and the activity log.
     */
    public function adminLabel(): string
    {
        $name = $this->getAttribute('name');

        return is_string($name) && $name !== '' ? $name : '#'.$this->getKey();
    }

    /**
     * Case-insensitive "contains" search across adminSearchColumns(). Blank terms match everything.
     *
     * @param  Builder<static>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        // Escape LIKE wildcards so "50%" searches literally (PostgreSQL default escape is "\").
        $pattern = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';

        $query->whereAny(static::adminSearchColumns(), 'ilike', $pattern);
    }

    /**
     * Filter by state: "active" → column true, "inactive" → false, anything else → no filter.
     *
     * @param  Builder<static>  $query
     */
    public function scopeWhereState(Builder $query, ?string $state): void
    {
        match ($state) {
            'active' => $query->where(static::adminStateColumn(), true),
            'inactive' => $query->where(static::adminStateColumn(), false),
            default => null,
        };
    }
}
