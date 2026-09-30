<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Key/value business setting. Read through SettingService (cached, M2+); keys in HISTORY.md.
 *
 * @property int $id
 * @property string $key Dotted, e.g. "booking.downpayment_percent"
 * @property string|null $value
 * @property string $group
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
        'group',
    ];
}
