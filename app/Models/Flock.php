<?php

namespace App\Models;

use App\Services\FlockLedger;
use Illuminate\Database\Eloquent\Model;

class Flock extends Model
{
    public const TYPES = [
        'layer' => 'Layers',
        'broiler' => 'Broilers',
        'pullet' => 'Pullets (growing)',
        'cockerel' => 'Cockerels',
        'other' => 'Other',
    ];

    public const STATUSES = ['active', 'inactive', 'sold', 'ended'];

    protected $fillable = [
        'name', 'breed', 'flock_type', 'house', 'initial_bird_count', 'current_bird_count',
        'opening_egg_stock', 'opening_feed_stock', 'date_of_arrival', 'age_in_weeks', 'status',
        'merged_into_id', 'closed_on', 'notes',
    ];

    protected $casts = [
        'initial_bird_count' => 'integer',
        'current_bird_count' => 'integer',
        'opening_egg_stock' => 'integer',
        'opening_feed_stock' => 'decimal:2',
        'date_of_arrival' => 'date',
        'closed_on' => 'date',
        'age_in_weeks' => 'integer',
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($flock) {
            if (empty($flock->current_bird_count)) {
                $flock->current_bird_count = $flock->initial_bird_count;
            }
            $flock->flock_type = $flock->flock_type ?: 'layer';
        });

        // Changing the starting numbers changes every running total.
        static::updated(function (Flock $flock) {
            if ($flock->wasChanged(['initial_bird_count', 'opening_egg_stock'])) {
                FlockLedger::touch($flock->id);
            }
        });
    }

    public function weekEntries()
    {
        return $this->hasMany(WeekEntry::class, 'flock_id');
    }

    public function dailyEntries()
    {
        return $this->hasMany(DailyEntry::class, 'flock_id');
    }

    public function mergedInto()
    {
        return $this->belongsTo(Flock::class, 'merged_into_id');
    }

    /** Flocks that were combined into this one. */
    public function mergedFrom()
    {
        return $this->hasMany(Flock::class, 'merged_into_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->flock_type ?? 'layer'] ?? ucfirst((string) $this->flock_type);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: 'Flock ' . $this->id;
    }

    /** Age in weeks today: age at arrival plus weeks since arrival (null when the arrival date is unknown). */
    public function getAgeWeeksNowAttribute(): ?int
    {
        if (! $this->date_of_arrival) {
            return $this->age_in_weeks ?: null;
        }

        return (int) $this->age_in_weeks + intdiv((int) $this->date_of_arrival->diffInDays(now()), 7);
    }

    // Kept for older screens: mortality as birds lost since the start of records
    public function updateCurrentBirdCount($newCount)
    {
        $this->current_bird_count = max(0, $newCount);
        $this->save();

        return $this;
    }

    /** Deaths to date (stolen and culled birds are not mortality). */
    public function calculateMortality()
    {
        return (int) $this->dailyEntries()->sum('daily_mortality');
    }

    public function getTotalMortalityAttribute()
    {
        return $this->calculateMortality();
    }

    public function getMortalityRateAttribute()
    {
        return $this->initial_bird_count > 0 ? ($this->calculateMortality() / $this->initial_bird_count) * 100 : 0;
    }

    public function getSurvivalRateAttribute()
    {
        return $this->initial_bird_count > 0 ? ($this->current_bird_count / $this->initial_bird_count) * 100 : 0;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
