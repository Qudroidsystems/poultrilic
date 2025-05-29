<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Flock extends Model
{
    protected $fillable = ['initial_bird_count', 'current_bird_count'];

    public function weekEntries()
    {
        return $this->hasMany(WeekEntry::class, 'flock_id');
    }
}

