<?php

namespace App\Models;

use App\Enums\GalleryCategory;
use Database\Factories\GalleryImageFactory;
use App\Enums\ImageVariant;
use App\Models\Concerns\AdminListable;
use App\Services\Content\ImageService;
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
    use AdminListable;

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
     * Columns matched by the admin search box (ILIKE).
     *
     * @return list<string>
     */
    public static function adminSearchColumns(): array
    {
        return ['caption'];
    }

    /**
     * The visibility flag drives the visible/hidden filter.
     */
    public static function adminStateColumn(): string
    {
        return 'is_visible';
    }

    /**
     * Caption, or "Image #id" when there is none.
     */
    public function adminLabel(): string
    {
        return $this->caption !== null && $this->caption !== '' ? $this->caption : 'Image #'.$this->id;
    }

    /**
     * Public URL of a resized version of the image (null when there is none).
     */
    public function imageUrl(ImageVariant $variant = ImageVariant::Large): ?string
    {
        return ImageService::url($this->path, $variant);
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
