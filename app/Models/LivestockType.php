<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LivestockType extends Model
{
    protected $fillable = ['name', 'slug', 'unit_label', 'count_label', 'icon', 'color', 'description', 'active', 'sort'];

    protected $casts = ['active' => 'boolean', 'sort' => 'integer'];

    protected static function booted()
    {
        static::saving(function (LivestockType $t) {
            if (! $t->slug) {
                $base = Str::slug($t->name) ?: 'type';
                $slug = $base;
                $i = 2;
                while (static::where('slug', $slug)->where('id', '!=', $t->id ?? 0)->exists()) {
                    $slug = $base . '-' . $i++;
                }
                $t->slug = $slug;
            }
        });
    }

    public function fields()
    {
        return $this->hasMany(LivestockField::class)->orderBy('sort')->orderBy('id');
    }

    public function units()
    {
        return $this->hasMany(LivestockUnit::class)->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")->orderBy('name');
    }

    public function records()
    {
        return $this->hasManyThrough(LivestockRecord::class, LivestockUnit::class);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    /** Numeric fields that can be totalled, charted and reported. */
    public function numericFields()
    {
        return $this->fields->filter(fn (LivestockField $f) => $f->isNumeric() && $f->aggregate !== 'none');
    }
}
