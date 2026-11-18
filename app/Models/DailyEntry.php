<?php

namespace App\Models;

use App\Services\FlockLedger;
use App\Support\Eggs;
use Illuminate\Database\Eloquent\Model;

class DailyEntry extends Model
{
    protected $fillable = [
        'week_entry_id', 'flock_id', 'day_number', 'entry_date',
        'daily_feeds', 'available_feeds', 'total_feeds_consumed',
        'daily_mortality', 'birds_stolen', 'birds_culled', 'sick_bay', 'total_mortality', 'current_birds',
        'reported_birds_alive', 'reported_total_deaths', 'avg_weight_g', 'bad_eggs', 'egg_stock',
        'notes', 'source', 'reported_at',
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
        'total_feeds_consumed' => 'decimal:2',
        'birds_stolen' => 'integer',
        'birds_culled' => 'integer',
        'bad_eggs' => 'integer',
        'egg_stock' => 'integer',
        'reported_birds_alive' => 'integer',
        'reported_total_deaths' => 'integer',
        'avg_weight_g' => 'decimal:1',
        'reported_at' => 'datetime',
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
            // ...and the other way round, so screens that still show the text never go stale.
            foreach (['eggs_produced' => 'daily_egg_production', 'eggs_sold' => 'daily_sold_egg'] as $int => $legacy) {
                if ($entry->isDirty($int) && ! $entry->isDirty($legacy)) {
                    $entry->{$legacy} = Eggs::format((int) $entry->{$int});
                }
            }
            foreach (['daily_egg_production', 'daily_sold_egg', 'outstanding_egg', 'total_sold_egg', 'total_egg_in_farm'] as $col) {
                if ($entry->{$col} === null) {
                    $entry->{$col} = '0 Cr 0PC';
                }
            }
            foreach (['sick_bay', 'available_feeds', 'total_mortality', 'current_birds', 'total_feeds_consumed', 'broken_egg'] as $col) {
                if ($entry->{$col} === null) {
                    $entry->{$col} = 0;
                }
            }
        });

        // Every running total (birds alive, deaths to date, feed to date, egg stock) is recalculated for the
        // whole flock whenever a day is added, changed or removed. See App\Services\FlockLedger.
        static::saved(function (DailyEntry $entry) {
            FlockLedger::touch($entry->flock_id);
            if ($entry->wasChanged('flock_id') && $entry->getOriginal('flock_id')) {
                FlockLedger::touch((int) $entry->getOriginal('flock_id'));
            }
        });

        static::deleted(function (DailyEntry $entry) {
            FlockLedger::touch($entry->flock_id);
        });
    }

    /** Birds lost today for any reason. */
    public function getLossesAttribute(): int
    {
        return (int) $this->daily_mortality + (int) $this->birds_stolen + (int) $this->birds_culled;
    }

    /** Eggs laid per bird alive that day, as a percentage (hen-day production). */
    public function getHenDayAttribute(): ?float
    {
        $birds = (int) $this->current_birds;
        return ($birds > 0 && $this->eggs_produced > 0) ? round($this->eggs_produced / $birds * 100, 1) : null;
    }

    /** Kept for older callers: recalculates the whole flock. */
    public function updateFlockBirdCount()
    {
        FlockLedger::rebuild((int) ($this->flock_id ?: optional($this->weekEntry)->flock_id));
    }
}
