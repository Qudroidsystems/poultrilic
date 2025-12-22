<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Flock extends Model
{
    protected $fillable = [
        'name', 'breed', 'initial_bird_count', 'current_bird_count',
        'date_of_arrival', 'age_in_weeks', 'status'
    ];

    protected $casts = [
        'initial_bird_count' => 'integer',
        'current_bird_count' => 'integer',
        'date_of_arrival' => 'date',
        'age_in_weeks' => 'integer',
        'created_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();
        
        // Set initial current_bird_count equal to initial_bird_count when creating
        static::creating(function ($flock) {
            if (empty($flock->current_bird_count)) {
                $flock->current_bird_count = $flock->initial_bird_count;
            }
        });
    }

    public function weekEntries()
    {
        return $this->hasMany(WeekEntry::class, 'flock_id');
    }

    // Method to update current bird count
    public function updateCurrentBirdCount($newCount)
    {
        $this->current_bird_count = max(0, $newCount);
        $this->save();
        return $this;
    }

    // Method to calculate mortality
    public function calculateMortality()
    {
        return max(0, $this->initial_bird_count - ($this->current_bird_count ?? $this->initial_bird_count));
    }

    // Dynamic accessor for total mortality
    public function getTotalMortalityAttribute()
    {
        return $this->calculateMortality();
    }
    
    // Dynamic accessor for mortality rate
    public function getMortalityRateAttribute()
    {
        if ($this->initial_bird_count > 0) {
            return ($this->calculateMortality() / $this->initial_bird_count) * 100;
        }
        return 0;
    }
    
    // Dynamic accessor for survival rate
    public function getSurvivalRateAttribute()
    {
        return 100 - $this->mortality_rate;
    }
    
    // Scope for active flocks
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}