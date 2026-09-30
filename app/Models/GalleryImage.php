<?php

namespace App\Models;

use App\Enums\GalleryCategory;
use Database\Factories\GalleryImageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Public gallery image (stored on the public disk).
 *
 * @property int $id
 * @property string $path
 * @property string|null $caption
 * @property GalleryCategory $category
 * @property bool $is_visible
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class GalleryImage extends Model
{
    /** @use HasFactory<GalleryImageFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'path',
        'caption',
        'category',
        'is_visible',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => GalleryCategory::class,
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Images shown publicly.
     *
     * @param  Builder<GalleryImage>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('is_visible', true);
    }

    /**
     * Admin-defined display order.
     *
     * @param  Builder<GalleryImage>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }
}
