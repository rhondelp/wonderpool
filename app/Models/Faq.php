<?php

namespace App\Models;

use Database\Factories\FaqFactory;
use App\Models\Concerns\AdminListable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Frequently asked question.
 *
 * @property int $id
 * @property string $question
 * @property string $answer
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Faq extends Model
{
    use AdminListable;

    /** @use HasFactory<FaqFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'question',
        'answer',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }


    /**
     * Columns matched by the admin search box (ILIKE).
     *
     * @return list<string>
     */
    public static function adminSearchColumns(): array
    {
        return ['question', 'answer'];
    }

    /**
     * The question, shortened for flashes and the activity log.
     */
    public function adminLabel(): string
    {
        return Str::limit($this->question, 60);
    }

    /**
     * FAQs shown publicly.
     *
     * @param  Builder<Faq>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Admin-defined display order.
     *
     * @param  Builder<Faq>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }
}
