<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\SettingGroup;
use App\Models\Setting;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Cached read/write access to the key/value `settings` table.
 * All rows are cached as one array (forever) and the cache is flushed on every write.
 * Missing keys fall back to the defaults declared in SettingGroup::fields().
 */
class SettingService
{
    /** Cache key holding every setting as [key => value]. */
    public const CACHE_KEY = 'settings.all';

    /**
     * @param  Cache  $cache  Application cache store
     * @param  ActivityLogger  $logger  Audit trail for updates
     */
    public function __construct(
        private readonly Cache $cache,
        private readonly ActivityLogger $logger,
    ) {
    }

    /**
     * Value of one setting as a string.
     *
     * @param  string  $key  Dotted key, e.g. "booking.downpayment_percent"
     * @param  string|null  $default  Returned when the key is neither stored nor declared
     */
    public function get(string $key, ?string $default = null): ?string
    {
        $all = $this->all();

        if (array_key_exists($key, $all)) {
            return $all[$key];
        }

        return self::declaredDefault($key) ?? $default;
    }

    /**
     * Value of one setting cast to int (e.g. percentages, hours, days).
     *
     * @param  string  $key  Dotted key
     * @param  int  $default  Returned when the setting is missing or not numeric
     */
    public function int(string $key, int $default = 0): int
    {
        $value = $this->get($key);

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * Current values of every field in a group, in declaration order (defaults filled in).
     *
     * @return array<string, string|null>
     */
    public function group(SettingGroup $group): array
    {
        $values = [];

        foreach (array_keys($group->fields()) as $key) {
            $values[$key] = $this->get($key);
        }

        return $values;
    }

    /**
     * Saves the given values for one group, ignores keys outside that group,
     * flushes the cache and records the changed keys in the activity log.
     *
     * @param  SettingGroup  $group  Group being edited
     * @param  array<string, mixed>  $values  [full key => new value], already validated
     * @return array<string, array{from: string|null, to: string|null}> Keys whose value changed
     */
    public function update(SettingGroup $group, array $values): array
    {
        $current = $this->group($group);
        $changes = [];

        foreach (array_keys($group->fields()) as $key) {
            if (! array_key_exists($key, $values)) {
                continue;
            }

            // Empty inputs arrive as null (ConvertEmptyStringsToNull); store them as "".
            $new = trim((string) ($values[$key] ?? ''));
            $old = $current[$key];

            if ($new === (string) $old) {
                continue;
            }

            Setting::query()->updateOrCreate(['key' => $key], ['value' => $new, 'group' => $group->value]);
            $changes[$key] = ['from' => $old, 'to' => $new];
        }

        if ($changes !== []) {
            $this->flush();
            $this->logger->log(ActivityAction::SettingsUpdated, null, ['group' => $group->value, 'changes' => $changes]);
        }

        return $changes;
    }

    /**
     * Every stored setting as [key => value], cached.
     *
     * @return array<string, string|null>
     */
    public function all(): array
    {
        /** @var array<string, string|null> */
        return $this->cache->rememberForever(self::CACHE_KEY, fn (): array => Setting::query()
            ->pluck('value', 'key')
            ->all());
    }

    /**
     * Drops the cached settings so the next read hits the database.
     */
    public function flush(): void
    {
        $this->cache->forget(self::CACHE_KEY);
    }

    /**
     * Default declared for a key in SettingGroup::fields(), or null if the key is unknown.
     *
     * @param  string  $key  Dotted key; the part before the first dot is the group
     */
    public static function declaredDefault(string $key): ?string
    {
        $group = SettingGroup::tryFrom(strstr($key, '.', true) ?: '');

        return $group?->fields()[$key]['default'] ?? null;
    }
}
