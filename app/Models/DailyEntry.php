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

    public function weekEntry()
    {
        return $this->belongsTo(WeekEntry::class);
    }
}
