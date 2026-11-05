<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedPurchase extends Model
{
    protected $fillable = ['purchase_date', 'bags', 'cost_per_bag', 'vendor', 'notes', 'expense_id', 'created_by'];

    protected $casts = [
        'purchase_date' => 'date:Y-m-d',
        'bags' => 'decimal:2',
        'cost_per_bag' => 'decimal:2',
    ];

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    public function getTotalCostAttribute(): float
    {
        return round((float) $this->bags * (float) $this->cost_per_bag, 2);
    }

    public function getIsOpeningStockAttribute(): bool
    {
        return (float) $this->cost_per_bag === 0.0;
    }
}
