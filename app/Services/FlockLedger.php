<?php

namespace App\Services;

use App\Models\DailyEntry;
use App\Models\Flock;
use App\Support\Eggs;
use Illuminate\Support\Facades\DB;

/**
 * Recalculates every running total for a flock from its daily inputs.
 *
 * The daily inputs (deaths, stolen, culled, feed fed, eggs laid / sold / broken / bad) are the source of
 * truth. Everything cumulative is derived here, walking the flock's entries oldest first, so a total can never
 * drift because an entry was edited, deleted or keyed in late:
 *
 *   birds alive      = birds at start of records - deaths - stolen - culled (to date)
 *   total_mortality  = deaths to date
 *   total feed       = bags fed to date
 *   egg stock        = opening stock + laid - sold - broken - bad (to date)
 *
 * The previous code summed only the current *week's* entries, so after week 1 the bird count was reset to
 * "initial - this week's deaths". Rebuilding per flock fixes that for good.
 */
class FlockLedger
{
    /** Flocks whose rebuild is waiting for the end of a batch (imports). */
    private static array $deferred = [];
    private static int $depth = 0;

    /**
     * Run $callback and rebuild each touched flock once at the end, instead of after every row.
     */
    public static function batch(callable $callback): mixed
    {
        self::$depth++;
        try {
            return $callback();
        } finally {
            self::$depth--;
            if (self::$depth === 0) {
                $ids = array_keys(self::$deferred);
                self::$deferred = [];
                foreach ($ids as $id) {
                    self::rebuild($id);
                }
            }
        }
    }

    /** Rebuild now, or at the end of the current batch. */
    public static function touch(?int $flockId): void
    {
        if (! $flockId) {
            return;
        }
        if (self::$depth > 0) {
            self::$deferred[$flockId] = true;

            return;
        }
        self::rebuild($flockId);
    }

    public static function rebuild(int|Flock $flock): void
    {
        $flock = $flock instanceof Flock ? $flock : Flock::find($flock);
        if (! $flock) {
            return;
        }

        DB::transaction(function () use ($flock) {
            $birds = (int) $flock->initial_bird_count;
            $deaths = 0;
            $feed = 0.0;
            $sold = 0;
            $stock = (int) ($flock->opening_egg_stock ?? 0);
            $weekTotals = [];   // week_entry_id => [feed, sold, net eggs]

            $entries = DailyEntry::query()->forFlock($flock->id)->oldestFirst()->get();

            foreach ($entries as $e) {
                $deaths += (int) $e->daily_mortality;
                $birds -= (int) $e->daily_mortality + (int) $e->birds_stolen + (int) $e->birds_culled;
                $feed += (float) $e->daily_feeds;
                $sold += (int) $e->eggs_sold;

                $net = (int) $e->eggs_produced - (int) $e->eggs_sold - (int) $e->broken_egg - (int) $e->bad_eggs;
                $stock += $net;

                $w = $e->week_entry_id;
                $weekTotals[$w] ??= ['feed' => 0.0, 'sold' => 0, 'net' => 0];
                $weekTotals[$w]['feed'] += (float) $e->daily_feeds;
                $weekTotals[$w]['sold'] += (int) $e->eggs_sold;
                $weekTotals[$w]['net'] += $net;

                $e->forceFill([
                    'total_mortality' => $deaths,
                    'current_birds' => max(0, $birds),
                    'total_feeds_consumed' => (int) round($feed),
                    'eggs_outstanding' => $net,
                    'egg_stock' => $stock,
                    // Legacy text columns, still read by older screens and exports.
                    'outstanding_egg' => Eggs::format(max(0, $net)),
                    'total_egg_in_farm' => Eggs::format(max(0, $stock)),
                    'total_sold_egg' => Eggs::format($sold),
                ]);

                if ($e->isDirty()) {
                    $e->saveQuietly();
                }
            }

            $flock->forceFill(['current_bird_count' => max(0, $birds)]);
            if ($flock->isDirty()) {
                $flock->saveQuietly();
            }
        });
    }

    public static function rebuildAll(): int
    {
        $n = 0;
        Flock::query()->orderBy('id')->each(function (Flock $f) use (&$n) {
            self::rebuild($f);
            $n++;
        });

        return $n;
    }

    /**
     * Birds alive just before $date (after every entry dated earlier), for validating a new day's losses.
     */
    public static function birdsBefore(Flock $flock, string $date, ?int $ignoreEntryId = null): int
    {
        $losses = DailyEntry::query()->forFlock($flock->id)
            ->where('entry_date', '<', $date)
            ->when($ignoreEntryId, fn ($q) => $q->where('id', '!=', $ignoreEntryId))
            ->selectRaw('COALESCE(SUM(daily_mortality + birds_stolen + birds_culled), 0) as l')
            ->value('l');

        return (int) $flock->initial_bird_count - (int) $losses;
    }

    /**
     * Problems worth a second look for one flock: typed totals that do not match the daily figures,
     * missing or late days, feed that does not add up. Each item: [date, severity, message].
     *
     * @return array<int, array{date: ?string, severity: string, message: string}>
     */
    public static function issues(Flock $flock): array
    {
        $issues = [];
        $entries = DailyEntry::query()->forFlock($flock->id)->oldestFirst()->get();
        if ($entries->isEmpty()) {
            return $issues;
        }

        // Typed death totals often include deaths from before records began; compare changes, not the offset.
        $firstTyped = $entries->first(fn ($e) => $e->reported_total_deaths !== null);
        $deathOffset = $firstTyped ? (int) $firstTyped->reported_total_deaths - (int) $firstTyped->total_mortality : 0;

        $prev = null;
        $prevBirdDiff = 0;
        $prevDeathDiff = 0;
        $negative = false;
        $late = [];
        foreach ($entries as $e) {
            $d = $e->entry_date?->toDateString();

            // Flag where a difference first appears or changes; a carried-over difference is the same problem.
            if ($e->reported_birds_alive !== null) {
                $diff = (int) $e->reported_birds_alive - (int) $e->current_birds;
                if ($diff !== $prevBirdDiff) {
                    $issues[] = ['date' => $d, 'severity' => $diff === 0 ? 'info' : 'warning', 'message' => $diff === 0
                        ? sprintf('Reported birds alive (%s) agree with the daily losses again.', number_format($e->reported_birds_alive))
                        : sprintf('Farm reported %s birds alive; the daily losses give %s (%s%d). Birds missing, miscounted or a death not recorded?',
                            number_format($e->reported_birds_alive), number_format($e->current_birds), $diff > 0 ? '+' : '', $diff)];
                    $prevBirdDiff = $diff;
                }
            }

            if ($e->reported_total_deaths !== null) {
                $diff = (int) $e->reported_total_deaths - $deathOffset - (int) $e->total_mortality;
                if ($diff !== $prevDeathDiff) {
                    $issues[] = ['date' => $d, 'severity' => 'info', 'message' => $diff === 0
                        ? 'Typed total deaths agree with the daily deaths again.'
                        : sprintf('Total deaths typed as %s; the daily deaths give %s.', number_format($e->reported_total_deaths), number_format($e->total_mortality + $deathOffset))];
                    $prevDeathDiff = $diff;
                }
            }

            if ($e->egg_stock < 0 && ! $negative) {
                $issues[] = ['date' => $d, 'severity' => 'warning', 'message' => sprintf(
                    'Eggs in farm go negative (%s): more eggs sold or written off than laid plus stock. Check the opening egg stock or sales.', number_format($e->egg_stock)
                )];
            }
            $negative = $e->egg_stock < 0;

            if ($prev) {
                $gap = (int) $prev->entry_date->diffInDays($e->entry_date);
                if ($gap > 1) {
                    $issues[] = ['date' => $d, 'severity' => 'info', 'message' => sprintf(
                        '%d day(s) with no record (%s to %s).',
                        $gap - 1, $prev->entry_date->copy()->addDay()->format('d M'), $e->entry_date->copy()->subDay()->format('d M Y')
                    )];
                }
            }

            if ($e->reported_at && $e->entry_date) {
                $delay = (int) $e->entry_date->diffInDays($e->reported_at->copy()->startOfDay(), false);
                if ($delay > 2) {
                    $late[] = $delay;
                }
            }

            $prev = $e;
        }

        if ($late) {
            $issues[] = ['date' => null, 'severity' => 'info', 'message' => sprintf(
                '%d of %d reports were sent more than 2 days late (worst %d days). Same-day records catch problems sooner.',
                count($late), $entries->count(), max($late)
            )];
        }

        return $issues;
    }
}
