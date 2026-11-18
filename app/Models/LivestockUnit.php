<?php

namespace App\Models;

use App\Services\LivestockLedger;
use Illuminate\Database\Eloquent\Model;

class LivestockUnit extends Model
{
    protected $fillable = ['livestock_type_id', 'name', 'code', 'breed', 'start_date', 'opening_count', 'current_count', 'status', 'notes'];

    protected $casts = ['start_date' => 'date', 'opening_count' => 'integer', 'current_count' => 'integer'];

    protected static function booted()
    {
        static::creating(fn (LivestockUnit $u) => $u->current_count = $u->current_count ?: $u->opening_count);
        static::updated(function (LivestockUnit $u) {
            if ($u->wasChanged('opening_count')) {
                LivestockLedger::rebuild($u);
            }
        });
    }

    public function type()
    {
        return $this->belongsTo(LivestockType::class, 'livestock_type_id');
    }

    public function records()
    {
        return $this->hasMany(LivestockRecord::class)->orderBy('record_date');
    }
}
