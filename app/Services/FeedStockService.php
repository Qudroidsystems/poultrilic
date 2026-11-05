<?php

namespace App\Services;

use App\Models\DailyEntry;
use App\Models\FeedPurchase;
use App\Support\FarmSettings;
use Illuminate\Support\Facades\Schema;

/**
 * Feed on hand = bags bought (opening stock counts as a free purchase)
 *              - bags fed since the first purchase, from the daily entries.
 *
 * Daily entries are counted from the date of the first purchase onwards, so
 * history from before the ledger was started never drives stock negative.
 */
class FeedStockService
{
    /** How many recent days of feeding are averaged to estimate daily use. */
    public const USAGE_WINDOW_DAYS = 7;

    /**
     * @return array{tracking: bool, purchased: float, consumed: float, on_hand: float, avg_daily_use: float,
     *               days_remaining: ?float, run_out_date: ?string, reorder_days: int, needs_reorder: bool,
     *               last_purchase: ?FeedPurchase, since: ?string}
     */
    public function snapshot(?\DateTimeInterface $today = null): array
    {
        $today = $today ?? now();
        $reorderDays = (int) FarmSettings::get('feed_reorder_days');

        $empty = [
            'tracking' => false, 'purchased' => 0.0, 'consumed' => 0.0, 'on_hand' => 0.0, 'avg_daily_use' => 0.0,
            'days_remaining' => null, 'run_out_date' => null, 'reorder_days' => $reorderDays,
            'needs_reorder' => false, 'last_purchase' => null, 'since' => null,
        ];

        if (! Schema::hasTable('feed_purchases')) {
            return $empty; // migration not run yet
        }

        $first = FeedPurchase::min('purchase_date');
        if ($first === null) {
            return $empty;
        }
        $first = substr($first, 0, 10);

        $purchased = (float) FeedPurchase::sum('bags');
        $consumed = (float) DailyEntry::where('entry_date', '>=', $first)->sum('daily_feeds');
        $onHand = round($purchased - $consumed, 2);

        $avg = $this->averageDailyUse($today, $first);
        $daysRemaining = $avg > 0 ? round(max($onHand, 0) / $avg, 1) : null;

        return [
            'tracking' => true,
            'purchased' => $purchased,
            'consumed' => $consumed,
            'on_hand' => $onHand,
            'avg_daily_use' => $avg,
            'days_remaining' => $daysRemaining,
            'run_out_date' => $daysRemaining !== null
                ? \Carbon\Carbon::instance(\DateTime::createFromInterface($today))->addDays((int) floor($daysRemaining))->toDateString()
                : null,
            'reorder_days' => $reorderDays,
            // Out of stock, or projected to run out within the reorder window.
            'needs_reorder' => $onHand <= 0 || ($daysRemaining !== null && $daysRemaining <= $reorderDays),
            'last_purchase' => FeedPurchase::orderByDesc('purchase_date')->orderByDesc('id')->first(),
            'since' => $first,
        ];
    }

    /**
     * Average bags fed per day over the last USAGE_WINDOW_DAYS days that have entries
     * (farm-wide: all flocks' feed for a date is summed first, then averaged over the dates).
     */
    private function averageDailyUse(\DateTimeInterface $today, string $since): float
    {
        $from = \Carbon\Carbon::instance(\DateTime::createFromInterface($today))
            ->subDays(self::USAGE_WINDOW_DAYS - 1)->toDateString();
        $from = max($from, $since);

        $perDay = DailyEntry::where('entry_date', '>=', $from)
            ->selectRaw('entry_date, SUM(daily_feeds) as bags')
            ->groupBy('entry_date')
            ->pluck('bags');

        return $perDay->isEmpty() ? 0.0 : round((float) $perDay->avg(), 2);
    }
}
