<?php

namespace App\Models;

use App\Services\LivestockLedger;
use Illuminate\Database\Eloquent\Model;

class LivestockRecord extends Model
{
    protected $fillable = ['livestock_unit_id', 'record_date', 'values', 'count_after', 'notes', 'source', 'created_by'];

    protected $casts = ['record_date' => 'date:Y-m-d', 'values' => 'array', 'count_after' => 'integer'];

    protected static function booted()
    {
        static::saved(fn (LivestockRecord $r) => LivestockLedger::rebuild($r->livestock_unit_id));
        static::deleted(fn (LivestockRecord $r) => LivestockLedger::rebuild($r->livestock_unit_id));
    }

    public function unit()
    {
        return $this->belongsTo(LivestockUnit::class, 'livestock_unit_id');
    }

    public function value(string $key): mixed
    {
        return $this->values[$key] ?? null;
    }
}
