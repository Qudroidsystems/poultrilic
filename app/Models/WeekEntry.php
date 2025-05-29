<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeekEntry extends Model
{
    use HasFactory;

    protected $fillable = ['flock_id', 'week_name'];

    public function flock()
    {
        return $this->belongsTo(Flock::class);
    }

    public function dailyEntries()
    {
        return $this->hasMany(DailyEntry::class);
    }
}