<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyEntry extends Model
{
    protected $fillable = [
        'week_entry_id', 'day_number', 'daily_feeds', 'available_feeds', 'total_feeds_consumed',
        'daily_mortality', 'sick_bay', 'total_mortality', 'current_birds', 'daily_egg_production',
        'daily_sold_egg', 'total_sold_egg', 'broken_egg', 'outstanding_egg',
        'total_egg_in_farm', 'drugs', 'reorder_feeds'
    ];

    protected $casts = [
        'daily_feeds' => 'decimal:2',
        'available_feeds' => 'decimal:2',
        'reorder_feeds' => 'decimal:2',
        'total_feeds_consumed' => 'integer',
        'daily_mortality' => 'integer',
        'sick_bay' => 'integer',
        'total_mortality' => 'integer',
        'current_birds' => 'integer',
        'broken_egg' => 'integer',
        'day_number' => 'integer',
    ];
    
    public function weekEntry()
    {
        return $this->belongsTo(WeekEntry::class);
    }
    
    // Update flock's current bird count when daily mortality is recorded
    protected static function boot()
    {
        parent::boot();
        
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
            $weekEntry = $this->weekEntry;
            if ($weekEntry && $weekEntry->flock) {
                $flock = $weekEntry->flock;
                
                // Get the latest current_birds from this flock's entries
                $latestEntry = DailyEntry::whereHas('weekEntry', function($q) use ($flock) {
                    $q->where('flock_id', $flock->id);
                })->orderBy('created_at', 'desc')->first();
                
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