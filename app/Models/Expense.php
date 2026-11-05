<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    public const CATEGORIES = [
        'feed'        => 'Feed',
        'drugs'       => 'Drugs & vaccines',
        'labor'       => 'Labor',
        'utilities'   => 'Utilities',
        'transport'   => 'Transport',
        'maintenance' => 'Maintenance & repairs',
        'birds'       => 'Birds (purchase)',
        'packaging'   => 'Packaging (crates, trays)',
        'other'       => 'Other',
    ];

    protected $fillable = ['flock_id', 'expense_date', 'category', 'description', 'vendor', 'amount', 'created_by'];

    protected $casts = [
        'expense_date' => 'date:Y-m-d',
        'amount' => 'decimal:2',
    ];

    public function flock()
    {
        return $this->belongsTo(Flock::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }

    public function scopeBetweenDates($query, $start, $end)
    {
        return $query->whereBetween('expense_date', [
            $start instanceof \DateTimeInterface ? $start->format('Y-m-d') : $start,
            $end instanceof \DateTimeInterface ? $end->format('Y-m-d') : $end,
        ]);
    }

    /**
     * Expenses charged to one flock, plus farm-wide expenses (flock_id null) when $includeShared is true.
     */
    public function scopeForFlock($query, $flockId, bool $includeShared = false)
    {
        return $query->where(function ($q) use ($flockId, $includeShared) {
            $q->where('flock_id', $flockId);
            if ($includeShared) {
                $q->orWhereNull('flock_id');
            }
        });
    }
}
