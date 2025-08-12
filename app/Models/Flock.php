<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Flock extends Model
{
    protected $fillable = ['initial_bird_count', 'current_bird_count'];

    protected $casts = [
        'initial_bird_count' => 'integer',
        'current_bird_count' => 'integer',
        'created_at' => 'datetime'
    ];

    public function weekEntries()
    {
        return $this->hasMany(WeekEntry::class, 'flock_id');
    }

    // Dynamic accessor for total mortality
    public function getTotalMortalityAttribute()
    {
        return $this->weekEntries()->join('daily_entries', 'week_entries.id', '=', 'daily_entries.week_entry_id')
            ->sum('daily_entries.daily_mortality');
    }
}

