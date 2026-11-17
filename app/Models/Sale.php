<?php

namespace App\Models;

use App\Support\Eggs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
    protected $fillable = ['invoice_number', 'customer_id', 'sale_date', 'eggs', 'price_per_egg', 'total', 'notes', 'created_by'];

    protected $casts = [
        'sale_date' => 'date:Y-m-d',
        'eggs' => 'integer',
        'price_per_egg' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments()
    {
        return $this->hasMany(SalePayment::class)->orderBy('paid_on')->orderBy('id');
    }

    public function scopeBetweenDates($query, $start, $end)
    {
        return $query->whereBetween('sale_date', [
            $start instanceof \DateTimeInterface ? $start->format('Y-m-d') : $start,
            $end instanceof \DateTimeInterface ? $end->format('Y-m-d') : $end,
        ]);
    }

    /** paid | partial | unpaid | open (still owing), decided in SQL so it can be paginated. */
    public function scopeWithStatus($query, string $status)
    {
        $paid = '(select coalesce(sum(amount), 0) from sale_payments where sale_payments.sale_id = sales.id)';

        return match ($status) {
            'paid' => $query->whereRaw("$paid >= sales.total"),
            'partial' => $query->whereRaw("$paid > 0 and $paid < sales.total"),
            'unpaid' => $query->whereRaw("$paid = 0"),
            'open' => $query->whereRaw("$paid < sales.total"),   // unpaid or part paid
            default => $query,
        };
    }

    /** Total paid so far; uses the withSum value when the list already loaded it. */
    public function getPaidAttribute(): float
    {
        if (array_key_exists('payments_sum_amount', $this->attributes)) {
            $sum = $this->attributes['payments_sum_amount'];
        } elseif ($this->relationLoaded('payments')) {
            $sum = $this->payments->sum('amount');
        } else {
            $sum = $this->payments()->sum('amount');
        }

        return round((float) $sum, 2);
    }

    public function getBalanceAttribute(): float
    {
        return round(max((float) $this->total - $this->paid, 0), 2);
    }

    public function getStatusAttribute(): string
    {
        if ($this->paid <= 0) {
            return 'unpaid';
        }

        return $this->paid >= (float) $this->total ? 'paid' : 'partial';
    }

    public function getQuantityLabelAttribute(): string
    {
        return Eggs::format($this->eggs);
    }

    public function getCustomerNameAttribute(): string
    {
        return $this->customer?->name ?? 'Walk-in customer';
    }

    public static function computeTotal(int $eggs, float|string $pricePerEgg): float
    {
        return round($eggs * (float) $pricePerEgg, 2);
    }

    /**
     * Next invoice number, INV-<year>-<4 digits>, one sequence per calendar year of the sale date.
     * Call inside a transaction; the unique index is the final guard against duplicates.
     */
    public static function nextInvoiceNumber(int $year): string
    {
        $prefix = "INV-$year-";
        $last = static::where('invoice_number', 'like', $prefix . '%')->max('invoice_number');
        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Money still owed across every invoice.
     *
     * @return array{outstanding: float, invoices: int}
     */
    public static function receivables(): array
    {
        $rows = static::query()
            ->select('sales.id', 'sales.total')
            ->selectSub(
                SalePayment::query()->selectRaw('coalesce(sum(amount), 0)')->whereColumn('sale_payments.sale_id', 'sales.id'),
                'paid'
            )
            ->get();

        $open = $rows->filter(fn ($r) => (float) $r->total - (float) $r->paid > 0.004);

        return [
            'outstanding' => round($open->sum(fn ($r) => (float) $r->total - (float) $r->paid), 2),
            'invoices' => $open->count(),
        ];
    }
}
