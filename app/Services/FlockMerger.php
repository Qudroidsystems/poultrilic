<?php

namespace App\Services;

use App\Models\DailyEntry;
use App\Models\Flock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Combine flocks (e.g. 650 + 770 into one house). The source flocks are closed on the merge date and point at
 * the flock they went into; their records stay with them, so their history and reports are kept.
 * The target is either an existing flock (records already started) or a new one created with the birds the
 * sources had alive on the merge date.
 */
class FlockMerger
{
    /** @param  array<int>  $sourceIds */
    public function merge(array $sourceIds, string $date, ?int $targetId = null, ?string $newName = null): Flock
    {
        $date = Carbon::parse($date)->toDateString();
        $sources = Flock::whereIn('id', $sourceIds)->get();
        if ($sources->count() < 1 || ($targetId === null && $sources->count() < 2)) {
            throw ValidationException::withMessages(['sources' => ['Choose at least two flocks to combine, or one flock and the flock it moved into.']]);
        }
        if ($targetId && in_array($targetId, $sourceIds, true)) {
            throw ValidationException::withMessages(['target_id' => ['The flock they go into cannot also be one of the flocks being combined.']]);
        }
        foreach ($sources as $s) {
            if (DailyEntry::forFlock($s->id)->where('entry_date', '>', $date)->exists()) {
                throw ValidationException::withMessages(['date' => ["{$s->display_name} has records after {$date}. Choose a merge date on or after its last record."]]);
            }
        }

        return DB::transaction(function () use ($sources, $date, $targetId, $newName) {
            if ($targetId) {
                $target = Flock::findOrFail($targetId);
            } else {
                $birds = $sources->sum(fn (Flock $f) => FlockLedger::birdsBefore($f, Carbon::parse($date)->addDay()->toDateString()));
                $first = $sources->first();
                $target = Flock::create([
                    'name' => $newName ?: 'Merged ' . $sources->pluck('name')->implode(' + '),
                    'breed' => $first->breed,
                    'flock_type' => $first->flock_type,
                    'house' => $first->house,
                    'initial_bird_count' => $birds,
                    'current_bird_count' => $birds,
                    'date_of_arrival' => Carbon::parse($date)->addDay()->toDateString(),
                    'age_in_weeks' => (int) round($sources->avg(fn ($f) => $f->age_weeks_now ?? 0)),
                    'status' => 'active',
                    'notes' => 'Formed by combining ' . $sources->pluck('name')->implode(', ') . " on {$date}.",
                ]);
            }

            foreach ($sources as $s) {
                $s->forceFill(['status' => 'ended', 'merged_into_id' => $target->id, 'closed_on' => $date])->save();
            }

            return $target;
        });
    }
}
