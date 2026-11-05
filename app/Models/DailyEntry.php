<?php

namespace App\Models;

use App\Support\Eggs;
use Illuminate\Database\Eloquent\Model;

class DailyEntry extends Model
{
    protected $fillable = [
        'week_entry_id', 'flock_id', 'day_number', 'entry_date',
        'daily_feeds', 'available_feeds', 'total_feeds_consumed',
        'daily_mortality', 'sick_bay', 'total_mortality', 'current_birds',
        // Integer egg counts (pieces) - the source of truth for all reporting.
        'eggs_produced', 'eggs_sold', 'eggs_outstanding',
        // Legacy "11 Cr 12PC" text columns, still written for backward compatibility.
        'daily_egg_production', 'daily_sold_egg', 'total_sold_egg',
        'broken_egg', 'outstanding_egg', 'total_egg_in_farm',
        'drugs', 'reorder_feeds',
    ];

    protected $casts = [
        'entry_date' => 'date:Y-m-d',
        'daily_feeds' => 'decimal:2',
        'available_feeds' => 'decimal:2',
        'reorder_feeds' => 'decimal:2',
        'total_feeds_consumed' => 'integer',
        'daily_mortality' => 'integer',
        'sick_bay' => 'integer',
        'total_mortality' => 'integer',
        'current_birds' => 'integer',
        'eggs_produced' => 'integer',
        'eggs_sold' => 'integer',
        'eggs_outstanding' => 'integer',
        'broken_egg' => 'integer',
        'day_number' => 'integer',
    ];

    public function weekEntry()
    {
        return $this->belongsTo(WeekEntry::class);
    }

    public function flock()
    {
        return $this->belongsTo(Flock::class);
    }

    /**
     * Whether any drug was administered that day ("Nil" and blank mean none).
     */
    public function getHasDrugsAttribute(): bool
    {
        $drugs = trim((string) $this->drugs);

        return $drugs !== '' && strcasecmp($drugs, 'Nil') !== 0;
    }

    /**
     * Restrict to a flock using the direct flock_id column.
     */
    public function scopeForFlock($query, $flockId)
    {
        return $query->where('flock_id', $flockId);
    }

    /**
     * Restrict to entries dated within [$start, $end] (inclusive).
     */
    public function scopeBetweenDates($query, $start, $end)
    {
        return $query->whereBetween('entry_date', [
            $start instanceof \DateTimeInterface ? $start->format('Y-m-d') : $start,
            $end instanceof \DateTimeInterface ? $end->format('Y-m-d') : $end,
        ]);
    }

    /**
     * Newest first by the date the entry describes, then by id for same-day entries.
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderByDesc('entry_date')->orderByDesc('id');
    }

    public function scopeOldestFirst($query)
    {
        return $query->orderBy('entry_date')->orderBy('id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (DailyEntry $entry) {
            if (empty($entry->entry_date)) {
                $entry->entry_date = now()->toDateString();
            }

            if (empty($entry->flock_id) && $entry->week_entry_id) {
                $entry->flock_id = WeekEntry::whereKey($entry->week_entry_id)->value('flock_id');
            }
        });

        // Keep the integer columns in step with the legacy text columns when only the
        // latter were supplied (e.g. an import or tinker session written the old way).
        static::saving(function (DailyEntry $entry) {
            foreach ([
                'eggs_produced' => 'daily_egg_production',
                'eggs_sold' => 'daily_sold_egg',
                'eggs_outstanding' => 'outstanding_egg',
            ] as $int => $legacy) {
                if ($entry->isDirty($legacy) && ! $entry->isDirty($int)) {
                    $entry->{$int} = Eggs::parse($entry->{$legacy});
                }
            }
        });

        // Update flock's current bird count when daily mortality is recorded
        static::created(function ($dailyEntry) {
            $dailyEntry->updateFlockBirdCount();
        });

        static::updated(function ($dailyEntry) {
            if ($dailyEntry->isDirty('daily_mortality')) {
                $dailyEntry->updateFlockBirdCount();
            }
        });
    }

    public function updateFlockBirdCount()
    {
        try {
            $flockId = $this->flock_id ?: optional($this->weekEntry)->flock_id;
            $flock = $flockId ? Flock::find($flockId) : null;

            if ($flock) {
                // The latest entry (by the date it describes) carries the current bird count.
                $latestEntry = static::query()->forFlock($flock->id)->latestFirst()->first();

                if ($latestEntry) {
                    $flock->current_bird_count = $latestEntry->current_birds;
                    $flock->save();
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error updating flock bird count: ' . $e->getMessage());
        }
    }
}
