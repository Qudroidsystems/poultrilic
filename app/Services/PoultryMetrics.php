<?php

namespace App\Services;

use App\Models\DailyEntry;
use App\Models\Expense;
use App\Models\Flock;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Support\FarmSettings;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Poultry analytics, worked out the way a vet or farm consultant would, from the flock-days in a period.
 *
 *  Hen-day production %   eggs laid / bird-days on laying days x 100 (bird-days = birds alive each day)
 *  Hen-housed eggs        eggs laid / birds at the start of the period
 *  Mortality %            deaths / birds at the start of the period x 100
 *  Feed per bird per day  feed kg x 1000 / bird-days (grams)
 *  Eggs per bag           eggs laid / bags fed to laying flocks
 *  Feed per dozen eggs    feed kg on laying days / (eggs / 12)
 *  Broken %               broken / eggs laid x 100
 *
 * Every figure is computed from the stored daily inputs and the ledger's birds-alive column, never from typed
 * running totals. Groups: all, day, week (ISO), month, flock.
 */
class PoultryMetrics
{
    /** Metric catalogue: key => [label, unit, format, higher_is_better]. Used by the report builder and API. */
    public const METRICS = [
        'days_reported'        => ['Days reported', 'days', 'int', null],
        'birds_start'          => ['Birds at start', 'birds', 'int', null],
        'birds_end'            => ['Birds at end', 'birds', 'int', null],
        'deaths'               => ['Deaths', 'birds', 'int', false],
        'birds_stolen'         => ['Stolen / missing', 'birds', 'int', false],
        'birds_culled'         => ['Culled / sold live', 'birds', 'int', null],
        'mortality_pct'        => ['Mortality', '%', 'pct2', false],
        'eggs_laid'            => ['Eggs laid', 'eggs', 'int', true],
        'crates_laid'          => ['Crates laid', 'crates', 'dec1', true],
        'hen_day_pct'          => ['Hen-day production', '%', 'pct1', true],
        'hen_housed_eggs'      => ['Hen-housed eggs', 'eggs/bird', 'dec1', true],
        'eggs_sold'            => ['Eggs sold (farm records)', 'eggs', 'int', true],
        'broken_eggs'          => ['Broken eggs', 'eggs', 'int', false],
        'bad_eggs'             => ['Bad eggs', 'eggs', 'int', false],
        'broken_pct'           => ['Broken + bad', '% of laid', 'pct2', false],
        'egg_stock_end'        => ['Eggs in farm (end)', 'eggs', 'int', null],
        'feed_bags'            => ['Feed used', 'bags', 'dec1', null],
        'feed_kg'              => ['Feed used', 'kg', 'dec0', null],
        'feed_g_bird_day'      => ['Feed per bird per day', 'g', 'dec1', null],
        'eggs_per_bag'         => ['Eggs per bag of feed', 'eggs', 'dec0', true],
        'feed_kg_per_dozen'    => ['Feed per dozen eggs', 'kg', 'dec2', false],
        'avg_weight_g'         => ['Average body weight', 'g', 'dec0', null],
        'drug_days'            => ['Days on medication', 'days', 'int', null],
        'report_delay_days'    => ['Typical report delay', 'days', 'dec1', false],
        'revenue_estimate'     => ['Egg value (sold x price)', '₦', 'money', true],
        'sales_invoiced'       => ['Sales invoiced', '₦', 'money', true],
        'payments_received'    => ['Payments received', '₦', 'money', true],
        'expenses'             => ['Expenses recorded', '₦', 'money', false],
        'net_cash'             => ['Payments - expenses', '₦', 'money', true],
    ];

    public const GROUPS = ['all' => 'Whole period', 'day' => 'Day', 'week' => 'Week', 'month' => 'Month', 'flock' => 'Flock'];

    /**
     * Totals for the period.
     *
     * @param  array<int>|null  $flockIds  null = every flock
     */
    public function summary(?array $flockIds = null, $from = null, $to = null): array
    {
        $rows = $this->series($flockIds, $from, $to, 'all');

        return $rows[0] ?? $this->compute(collect(), $flockIds, $from, $to);
    }

    /**
     * One row of metrics per group, oldest first. Each row has 'key' and 'label' plus every METRICS key.
     */
    public function series(?array $flockIds = null, $from = null, $to = null, string $group = 'month'): array
    {
        [$from, $to] = $this->range($from, $to);
        $entries = $this->entries($flockIds, $from, $to);

        if ($group === 'all') {
            $row = $this->compute($entries, $flockIds, $from, $to);

            return [['key' => 'all', 'label' => $this->rangeLabel($from, $to, $entries)] + $row];
        }

        $names = Flock::pluck('name', 'id');
        $grouped = $entries->groupBy(fn (DailyEntry $e) => match ($group) {
            'day' => $e->entry_date->toDateString(),
            'week' => $e->entry_date->format('o-\WW'),
            'month' => $e->entry_date->format('Y-m'),
            'flock' => (string) $e->flock_id,
            default => 'all',
        })->sortKeys();

        $out = [];
        foreach ($grouped as $key => $items) {
            $label = match ($group) {
                'day' => Carbon::parse($key)->format('d M Y'),
                'week' => 'Wk ' . substr($key, 6) . ' ' . substr($key, 0, 4) . ' (' . $items->min('entry_date')->format('d M') . ')',
                'month' => Carbon::parse($key . '-01')->format('M Y'),
                'flock' => $names[(int) $key] ?? ('Flock ' . $key),
                default => (string) $key,
            };
            $groupFlocks = $group === 'flock' ? [(int) $key] : $flockIds;
            $out[] = ['key' => (string) $key, 'label' => $label] + $this->compute($items, $groupFlocks, null, null, false);
        }

        return $out;
    }

    /**
     * Per-flock series over time (e.g. monthly hen-day for each flock), for multi-line charts:
     * ['labels' => [...], 'datasets' => [['flock' => name, 'data' => [...]], ...]]
     */
    public function byFlockOverTime(string $metric, ?array $flockIds = null, $from = null, $to = null, string $group = 'month'): array
    {
        [$from, $to] = $this->range($from, $to);
        $flocks = Flock::query()->when($flockIds, fn ($q) => $q->whereIn('id', $flockIds))->orderBy('id')->get();
        $labels = [];
        $sets = [];
        foreach ($flocks as $flock) {
            $rows = collect($this->series([$flock->id], $from, $to, $group))->keyBy('key');
            if ($rows->isEmpty()) {
                continue;
            }
            foreach ($rows as $k => $r) {
                $labels[$k] = $r['label'];
            }
            $sets[] = ['flock' => $flock->display_name, 'flock_id' => $flock->id, 'rows' => $rows];
        }
        ksort($labels);

        return [
            'labels' => array_values($labels),
            'datasets' => array_map(fn ($s) => [
                'name' => $s['flock'],
                'flock_id' => $s['flock_id'],
                'data' => array_map(fn ($k) => isset($s['rows'][$k]) ? $s['rows'][$k][$metric] : null, array_keys($labels)),
            ], $sets),
        ];
    }

    /** Snapshot per flock for status tables: lifetime totals plus the latest month's laying rate. */
    public function flockTable(?array $flockIds = null, bool $includeClosed = true): array
    {
        $flocks = Flock::query()
            ->when($flockIds, fn ($q) => $q->whereIn('id', $flockIds))
            ->when(! $includeClosed, fn ($q) => $q->where('status', 'active'))
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")->orderBy('id')->get();

        $out = [];
        foreach ($flocks as $f) {
            $life = $this->summary([$f->id]);
            $last = DailyEntry::forFlock($f->id)->max('entry_date');
            $recent = $last ? $this->summary([$f->id], Carbon::parse($last)->subDays(29), Carbon::parse($last)) : null;
            $out[] = [
                'flock' => $f,
                'first' => DailyEntry::forFlock($f->id)->min('entry_date'),
                'last' => $last,
                'lifetime' => $life,
                'recent' => $recent,
                'issues' => count(FlockLedger::issues($f)),
            ];
        }

        return $out;
    }

    /**
     * Rule-based observations for reports ("what should I look at?"). Each: [severity, title, detail].
     */
    public function observations(?array $flockIds = null, $from = null, $to = null): array
    {
        [$from, $to] = $this->range($from, $to);
        $notes = [];
        $flocks = Flock::query()->when($flockIds, fn ($q) => $q->whereIn('id', $flockIds))->where('status', 'active')->get();
        $feed = app(FeedStockService::class)->snapshot();

        foreach ($flocks as $f) {
            $last = DailyEntry::forFlock($f->id)->latestFirst()->first();
            if (! $last) {
                continue;
            }
            $end = $last->entry_date->copy();
            $wk = $this->summary([$f->id], $end->copy()->subDays(6), $end);
            $prevWk = $this->summary([$f->id], $end->copy()->subDays(13), $end->copy()->subDays(7));
            $month = $this->summary([$f->id], $end->copy()->subDays(29), $end);

            // Mortality: layers normally lose well under 1% a month; young chicks a few % in the first weeks.
            $limit = in_array($f->flock_type, ['layer', 'cockerel']) ? 1.0 : 3.0;
            if ($month['mortality_pct'] !== null && $month['mortality_pct'] > $limit) {
                $notes[] = ['danger', "{$f->display_name}: high mortality",
                    sprintf('%s deaths in the last 30 days (%.1f%% of birds). Ask a vet to examine the flock and do post-mortems.', number_format($month['deaths']), $month['mortality_pct'])];
            }
            if ($wk['deaths'] > 0 && $prevWk['deaths'] > 0 && $wk['deaths'] >= $prevWk['deaths'] * 1.5 && $wk['deaths'] >= 10) {
                $notes[] = ['warning', "{$f->display_name}: deaths rising",
                    sprintf('%d deaths in the last 7 days against %d the week before.', $wk['deaths'], $prevWk['deaths'])];
            }

            if ($f->flock_type === 'layer' && $wk['hen_day_pct'] !== null && $prevWk['hen_day_pct'] !== null && $wk['hen_day_pct'] < $prevWk['hen_day_pct'] - 5) {
                $notes[] = ['warning', "{$f->display_name}: laying rate dropped",
                    sprintf('Hen-day fell from %.0f%% to %.0f%% week on week. Check feed, water, lighting and health.', $prevWk['hen_day_pct'], $wk['hen_day_pct'])];
            }

            if ($month['birds_stolen'] > 0) {
                $notes[] = ['warning', "{$f->display_name}: birds missing",
                    sprintf('%d birds recorded as stolen or missing in the last 30 days. Review security.', $month['birds_stolen'])];
            }

            if ($f->flock_type === 'layer' && $month['broken_pct'] !== null && $month['broken_pct'] > 3) {
                $notes[] = ['info', "{$f->display_name}: breakages",
                    sprintf('%.1f%% of eggs were broken or bad in the last 30 days (aim for under 2%%). Check collection, trays and calcium.', $month['broken_pct'])];
            }

            // Feed in store from the flock's own record.
            if ($wk['feed_bags'] > 0 && $last->available_feeds !== null && (float) $last->available_feeds > 0) {
                $perDay = $wk['feed_bags'] / max(1, $wk['days_reported']);
                $days = (float) $last->available_feeds / $perDay;
                if ($days <= FarmSettings::get('feed_reorder_days')) {
                    $notes[] = ['warning', "{$f->display_name}: feed running low",
                        sprintf('%s bags in store, about %.0f days at %.1f bags a day. Re-order now.', rtrim(rtrim(number_format((float) $last->available_feeds, 1), '0'), '.'), $days, $perDay)];
                }
            }

            $daysSince = (int) $end->diffInDays(now()->startOfDay());
            if ($daysSince > 3) {
                $notes[] = ['info', "{$f->display_name}: records behind",
                    sprintf('The latest record is for %s, %d days ago.', $end->format('j M Y'), $daysSince)];
            }
            if ($month['report_delay_days'] !== null && $month['report_delay_days'] > 3) {
                $notes[] = ['info', "{$f->display_name}: reports sent late",
                    sprintf('Reports arrive about %.0f days after the day they describe. Same-day records catch problems sooner.', $month['report_delay_days'])];
            }
        }

        if ($feed['tracking'] && $feed['needs_reorder']) {
            $notes[] = ['warning', 'Feed store', $feed['on_hand'] <= 0
                ? 'The feed ledger shows no feed on hand.'
                : sprintf('%.1f bags on hand, about %s days of feed. Re-order.', $feed['on_hand'], $feed['days_remaining'] ?? '?')];
        }

        return $notes;
    }

    // ------------------------------------------------------------------------------------------------

    private function range($from, $to): array
    {
        return [
            $from ? Carbon::parse($from)->startOfDay() : null,
            $to ? Carbon::parse($to)->endOfDay() : null,
        ];
    }

    private function rangeLabel(?Carbon $from, ?Carbon $to, Collection $entries): string
    {
        $a = $from ?? $entries->min('entry_date');
        $b = $to ?? $entries->max('entry_date');

        return ($a && $b) ? $a->format('d M Y') . ' – ' . $b->format('d M Y') : 'All records';
    }

    private function entries(?array $flockIds, ?Carbon $from, ?Carbon $to): Collection
    {
        return DailyEntry::query()
            ->with('flock:id,flock_type,merged_into_id,closed_on')
            ->when($flockIds, fn ($q) => $q->whereIn('flock_id', $flockIds))
            ->when($from, fn ($q) => $q->where('entry_date', '>=', $from->toDateString()))
            ->when($to, fn ($q) => $q->where('entry_date', '<=', $to->toDateString()))
            ->oldestFirst()
            ->get();
    }

    /**
     * Metrics for a set of flock-day entries. $from/$to only matter for the money figures, which come from the
     * sales, payments and expenses tables for the same dates.
     */
    private function compute(Collection $entries, ?array $flockIds, ?Carbon $from, ?Carbon $to, bool $money = true): array
    {
        $bagKg = (float) FarmSettings::bagWeightKg();
        $byFlock = $entries->groupBy('flock_id');

        // When a flock and the flock it was merged into are both in the set, the birds are the same birds:
        // count them at the start in the source flock and at the end in the merged flock.
        $present = $byFlock->keys()->map(fn ($k) => (int) $k)->all();
        $birdsStart = 0;
        $birdsEnd = 0;
        $stockEnd = 0;
        foreach ($byFlock as $flockId => $rows) {
            $first = $rows->first();
            $last = $rows->last();
            $flock = $first->flock;
            $mergedOnward = $flock && $flock->merged_into_id && in_array((int) $flock->merged_into_id, $present, true);
            $fedByMerge = in_array((int) $flockId, $mergedTargets ??= $byFlock->map(fn ($r) => $r->first()->flock?->merged_into_id)
                ->filter()->filter(fn ($t) => in_array((int) $t, $present, true))->map(fn ($t) => (int) $t)->values()->all(), true);

            if (! $fedByMerge) {
                $birdsStart += (int) $first->current_birds + $first->losses;   // alive before the first day's losses
            }
            if (! $mergedOnward) {
                $birdsEnd += (int) $last->current_birds;
                $stockEnd += (int) $last->egg_stock;
            }
        }

        $eggs = (int) $entries->sum('eggs_produced');
        $laying = $entries->filter(fn ($e) => (int) $e->eggs_produced > 0);
        $layingBirdDays = (int) $laying->sum('current_birds');
        $birdDays = (int) $entries->sum('current_birds');
        $feedBags = (float) $entries->sum('daily_feeds');
        $layingFeed = (float) $laying->sum('daily_feeds');
        $deaths = (int) $entries->sum('daily_mortality');
        $broken = (int) $entries->sum('broken_egg');
        $bad = (int) $entries->sum('bad_eggs');
        $sold = (int) $entries->sum('eggs_sold');
        $weights = $entries->pluck('avg_weight_g')->filter(fn ($w) => $w !== null && (float) $w > 0);

        $delays = $entries->map(function ($e) {
            $sent = $e->reported_at;
            return $sent ? max(0, (int) $e->entry_date->diffInDays($sent->copy()->startOfDay(), false)) : null;
        })->filter(fn ($d) => $d !== null)->sort()->values();

        $r = [
            'days_reported' => $entries->count(),
            'birds_start' => $birdsStart,
            'birds_end' => $birdsEnd,
            'deaths' => $deaths,
            'birds_stolen' => (int) $entries->sum('birds_stolen'),
            'birds_culled' => (int) $entries->sum('birds_culled'),
            'mortality_pct' => $birdsStart > 0 ? round($deaths / $birdsStart * 100, 2) : null,
            'eggs_laid' => $eggs,
            'crates_laid' => round($eggs / 30, 1),
            'hen_day_pct' => $layingBirdDays > 0 ? round($eggs / $layingBirdDays * 100, 1) : null,
            'hen_housed_eggs' => ($birdsStart > 0 && $eggs > 0) ? round($eggs / $birdsStart, 1) : null,
            'eggs_sold' => $sold,
            'broken_eggs' => $broken,
            'bad_eggs' => $bad,
            'broken_pct' => $eggs > 0 ? round(($broken + $bad) / $eggs * 100, 2) : null,
            'egg_stock_end' => $stockEnd,
            'feed_bags' => round($feedBags, 2),
            'feed_kg' => round($feedBags * $bagKg, 1),
            'feed_g_bird_day' => $birdDays > 0 ? round($feedBags * $bagKg * 1000 / $birdDays, 1) : null,
            'eggs_per_bag' => $layingFeed > 0 ? round($eggs / $layingFeed, 1) : null,
            'feed_kg_per_dozen' => $eggs > 0 ? round($layingFeed * $bagKg / ($eggs / 12), 2) : null,
            'avg_weight_g' => $weights->isNotEmpty() ? round($weights->avg(), 0) : null,
            'drug_days' => $entries->filter(fn ($e) => $e->has_drugs)->count(),
            'report_delay_days' => $delays->isNotEmpty() ? (float) $delays[intdiv($delays->count(), 2)] : null,
            'revenue_estimate' => round($sold * (float) FarmSettings::eggPrice(), 2),
            'sales_invoiced' => null,
            'payments_received' => null,
            'expenses' => null,
            'net_cash' => null,
        ];

        if ($money) {
            $a = $from?->toDateString() ?? ($entries->min('entry_date')?->toDateString());
            $b = $to?->toDateString() ?? ($entries->max('entry_date')?->toDateString());
            if ($a && $b) {
                $r = array_merge($r, $this->money($a, $b, $flockIds));
            }
        }

        return $r;
    }

    /** Money from the finance tables for [$a, $b]. Sales are farm-wide; expenses can be per flock. */
    public function money(string $a, string $b, ?array $flockIds = null): array
    {
        $sales = Schema::hasTable('sales') ? (float) Sale::whereBetween('sale_date', [$a, $b])->sum('total') : 0.0;
        $paid = Schema::hasTable('sale_payments') ? (float) SalePayment::whereBetween('paid_on', [$a, $b])->sum('amount') : 0.0;
        $exp = Schema::hasTable('expenses')
            ? (float) Expense::whereBetween('expense_date', [$a, $b])
                ->when($flockIds, fn ($q) => $q->where(fn ($w) => $w->whereIn('flock_id', $flockIds)->orWhereNull('flock_id')))
                ->sum('amount')
            : 0.0;

        return [
            'sales_invoiced' => round($sales, 2),
            'payments_received' => round($paid, 2),
            'expenses' => round($exp, 2),
            'net_cash' => round($paid - $exp, 2),
        ];
    }

    /** Format a metric value for display. */
    public static function format(string $metric, $value): string
    {
        if ($value === null) {
            return '–';
        }
        $fmt = self::METRICS[$metric][2] ?? 'dec1';

        return match ($fmt) {
            'int' => number_format((float) $value),
            'dec0' => number_format((float) $value, 0),
            'dec1' => number_format((float) $value, 1),
            'dec2' => number_format((float) $value, 2),
            'pct1' => number_format((float) $value, 1) . '%',
            'pct2' => number_format((float) $value, 2) . '%',
            'money' => '₦' . number_format((float) $value, 2),
            default => (string) $value,
        };
    }

    public static function label(string $metric): string
    {
        $m = self::METRICS[$metric] ?? null;
        if (! $m) {
            return $metric;
        }

        return in_array($m[1], ['%', '₦', 'eggs', 'birds', 'days', 'crates'], true) ? $m[0] : "{$m[0]} ({$m[1]})";
    }
}
