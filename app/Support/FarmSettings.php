<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Farm-wide prices and costs, editable from the Settings page.
 *
 * Values live in the `settings` table; anything not stored falls back to DEFAULTS
 * (the numbers that used to be hardcoded in DashboardController). If the table
 * does not exist yet (migration not run) the defaults are used, so the app keeps working.
 */
class FarmSettings
{
    private const CACHE_KEY = 'farm_settings';

    public const DEFAULTS = [
        'egg_price'             => 250,    // ₦ per egg
        'feed_cost_per_bag'     => 15000,  // ₦ per bag
        'bird_cost'             => 2000,   // ₦ per bird (point-of-lay purchase cost)
        'drug_cost_per_day'     => 5000,   // ₦ per day on which drugs were given
        'daily_labor_cost'      => 10000,  // ₦ per day
        'bag_weight_kg'         => 50,     // kg per feed bag
        'feed_reorder_days'     => 7,      // warn when feed on hand covers this many days or fewer
        'use_recorded_expenses' => 0,      // 1 = dashboard uses recorded expenses instead of the estimates above
    ];

    /** Human labels, units and validation used by the settings page. */
    public const FIELDS = [
        'egg_price'             => ['label' => 'Egg price', 'unit' => '₦ per egg'],
        'feed_cost_per_bag'     => ['label' => 'Feed cost', 'unit' => '₦ per bag'],
        'bag_weight_kg'         => ['label' => 'Feed bag weight', 'unit' => 'kg'],
        'feed_reorder_days'     => ['label' => 'Feed reorder warning', 'unit' => 'days of feed left'],
        'bird_cost'             => ['label' => 'Cost per bird', 'unit' => '₦ per bird'],
        'drug_cost_per_day'     => ['label' => 'Drug cost', 'unit' => '₦ per day drugs are given'],
        'daily_labor_cost'      => ['label' => 'Labor cost', 'unit' => '₦ per day'],
    ];

    private static ?array $memo = null;
    private static ?int $memoApp = null;   // app instance the memo belongs to

    public static function all(): array
    {
        $appId = spl_object_id(app());
        if (self::$memo !== null && self::$memoApp === $appId) {
            return self::$memo;
        }

        try {
            $stored = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::pluck('value', 'key')->all());
        } catch (\Throwable $e) {
            $stored = []; // table missing (pre-migration) or cache unavailable
        }

        $merged = self::DEFAULTS;
        foreach ($stored as $key => $value) {
            if (array_key_exists($key, self::DEFAULTS) && $value !== null && $value !== '' && is_numeric($value)) {
                $merged[$key] = $value + 0;
            }
        }

        self::$memoApp = $appId;

        return self::$memo = $merged;
    }

    public static function get(string $key): float|int
    {
        return self::all()[$key] ?? self::DEFAULTS[$key];
    }

    /**
     * Persist one or more settings. Unknown keys are ignored.
     */
    public static function set(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! array_key_exists($key, self::DEFAULTS)) {
                continue;
            }
            Setting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }

        self::flush();
    }

    public static function flush(): void
    {
        self::$memo = null;
        try {
            Cache::forget(self::CACHE_KEY);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public static function eggPrice(): float|int          { return self::get('egg_price'); }
    public static function feedCostPerBag(): float|int    { return self::get('feed_cost_per_bag'); }
    public static function birdCost(): float|int          { return self::get('bird_cost'); }
    public static function drugCostPerDay(): float|int    { return self::get('drug_cost_per_day'); }
    public static function dailyLaborCost(): float|int    { return self::get('daily_labor_cost'); }
    public static function bagWeightKg(): float|int       { return self::get('bag_weight_kg'); }
    public static function useRecordedExpenses(): bool    { return (bool) self::get('use_recorded_expenses'); }
}
