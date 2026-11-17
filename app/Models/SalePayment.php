<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalePayment extends Model
{
    public const METHODS = [
        'cash' => 'Cash',
        'transfer' => 'Bank transfer',
        'pos' => 'POS',
        'other' => 'Other',
    ];

    protected $fillable = ['sale_id', 'paid_on', 'amount', 'method', 'reference', 'created_by'];

    protected $casts = [
        'paid_on' => 'date:Y-m-d',
        'amount' => 'decimal:2',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function getReceiptNumberAttribute(): string
    {
        return 'RCT-' . str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function getMethodLabelAttribute(): string
    {
        return self::METHODS[$this->method] ?? ucfirst($this->method);
    }
}
