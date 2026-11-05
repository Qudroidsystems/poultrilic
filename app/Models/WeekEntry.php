<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WeekEntry extends Model
{
    use HasFactory;

    protected $fillable = ['flock_id', 'week_name', 'week_number'];

    public function flock()
    {
        return $this->belongsTo(Flock::class);
    }

    public function dailyEntries()
    {
        return $this->hasMany(DailyEntry::class);
    }

    // Eggs still in the farm for this week (pieces), summed from the integer column
    public function getTotalEggInFarmAttribute()
    {
        return (int) $this->dailyEntries()->sum('eggs_outstanding');
    }

    // Calculate total feed consumed for the week
    public function getTotalFeedConsumedAttribute()
    {
        return $this->dailyEntries()->sum('daily_feeds');
    }

    // Calculate total eggs produced for the week (pieces)
    public function getTotalEggsProducedAttribute()
    {
        return (int) $this->dailyEntries()->sum('eggs_produced');
    }
}
