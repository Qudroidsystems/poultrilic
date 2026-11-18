<?php

namespace App\Services;

use App\Models\DailyEntry;
use App\Models\Flock;
use App\Models\WeekEntry;
use App\Support\Eggs;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * One place that saves a flock-day, used by the web form, the mobile API and the Excel import.
 *
 * Callers pass raw daily inputs (integer egg pieces, bags of feed, birds lost). Week and day are worked out from
 * the flock's arrival date and age when they are not given, the entry is saved, and the flock ledger recalculates
 * every running total.
 */
class DailyEntryRecorder
{
    /** Inputs a day can carry. Eggs are whole pieces; feed is in bags. */
    public const INPUTS = [
        'entry_date', 'daily_feeds', 'available_feeds', 'daily_mortality', 'birds_stolen', 'birds_culled', 'sick_bay',
        'eggs_produced', 'eggs_sold', 'broken_egg', 'bad_eggs', 'drugs', 'notes', 'reorder_feeds', 'avg_weight_g',
        'reported_birds_alive', 'reported_total_deaths', 'reported_at', 'source',
    ];

    /**
     * Save a day for $flock. With $entry, that row is updated; otherwise a row for the same date is updated
     * when $upsert is true, or a new one is created.
     *
     * @throws ValidationException when the day's losses exceed the birds alive.
     */
    public function record(Flock $flock, array $data, ?DailyEntry $entry = null, bool $upsert = false, ?WeekEntry $week = null): DailyEntry
    {
        $date = Carbon::parse($data['entry_date'] ?? now())->toDateString();
        $data['entry_date'] = $date;

        if (! $entry && $upsert) {
            $entry = DailyEntry::forFlock($flock->id)->where('entry_date', $date)->first();
        }

        $losses = (int) ($data['daily_mortality'] ?? 0) + (int) ($data['birds_stolen'] ?? 0) + (int) ($data['birds_culled'] ?? 0);
        $alive = FlockLedger::birdsBefore($flock, $date, $entry?->id);
        if ($losses > max(0, $alive)) {
            throw ValidationException::withMessages([
                'daily_mortality' => [sprintf('Deaths, stolen and culled birds (%d) are more than the %d birds alive before %s.', $losses, max(0, $alive), Carbon::parse($date)->format('j M Y'))],
            ]);
        }

        [$weekNo, $dayNo] = $this->weekAndDay($flock, $date);
        if (! $week) {
            $week = $entry?->weekEntry && ! isset($data['week_number'])
                ? $entry->weekEntry
                : $this->week($flock, (int) ($data['week_number'] ?? $weekNo));
        }

        $values = array_intersect_key($data, array_flip(self::INPUTS));
        $values += [
            'daily_feeds' => 0, 'available_feeds' => 0, 'daily_mortality' => 0, 'sick_bay' => 0,
            'eggs_produced' => 0, 'eggs_sold' => 0, 'broken_egg' => 0,
        ];
        foreach (['eggs_produced', 'eggs_sold', 'broken_egg', 'bad_eggs', 'daily_mortality', 'birds_stolen', 'birds_culled', 'sick_bay'] as $int) {
            if (array_key_exists($int, $values)) {
                $values[$int] = max(0, (int) $values[$int]);
            }
        }

        $values['flock_id'] = $flock->id;
        $values['week_entry_id'] = $week->id;
        $values['day_number'] = (int) ($data['day_number'] ?? $dayNo);
        $values['daily_egg_production'] = Eggs::format($values['eggs_produced']);
        $values['daily_sold_egg'] = Eggs::format($values['eggs_sold']);

        if ($entry) {
            $entry->fill($values)->save();
        } else {
            $entry = DailyEntry::create($values);
        }

        return $entry->refresh();
    }

    /**
     * [week, day] for a date, counted the way the farm does: from the birds' age, so a layer flock that arrived
     * at 18 weeks is in "Week 19" in its first week. Without an arrival date, the flock's first record is day 1.
     */
    public function weekAndDay(Flock $flock, string $date): array
    {
        $start = $flock->date_of_arrival
            ?? DailyEntry::forFlock($flock->id)->min('entry_date')
            ?? $date;
        $start = Carbon::parse($start)->startOfDay();

        $ageDays = (int) ($flock->age_in_weeks ?? 0) * 7 + (int) $start->diffInDays(Carbon::parse($date)->startOfDay(), false) + 1;
        $ageDays = max(1, $ageDays);

        return [intdiv($ageDays - 1, 7) + 1, (($ageDays - 1) % 7) + 1];
    }

    public function week(Flock $flock, int $weekNo): WeekEntry
    {
        return WeekEntry::firstOrCreate(['flock_id' => $flock->id, 'week_name' => 'Week ' . $weekNo]);
    }
}
