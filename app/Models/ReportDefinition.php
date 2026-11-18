<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportDefinition extends Model
{
    public const PERIODS = [
        'last_7' => 'Last 7 days', 'last_30' => 'Last 30 days', 'last_90' => 'Last 90 days',
        'this_month' => 'This month', 'last_month' => 'Last month', 'this_year' => 'This year',
        'last_12m' => 'Last 12 months', 'all' => 'All records', 'custom' => 'Custom dates',
    ];

    public const CHARTS = ['line' => 'Line (trend)', 'bar' => 'Bars (compare)', 'table' => 'Table only'];

    protected $fillable = [
        'name', 'source', 'metrics', 'group_by', 'chart', 'period', 'date_from', 'date_to', 'filters',
        'per_entity', 'pinned', 'description', 'created_by',
    ];

    protected $casts = [
        'metrics' => 'array', 'filters' => 'array', 'per_entity' => 'boolean', 'pinned' => 'boolean',
        'date_from' => 'date', 'date_to' => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function livestockTypeId(): ?int
    {
        return str_starts_with($this->source, 'livestock:') ? (int) substr($this->source, 10) : null;
    }

    /** [from, to] as Y-m-d (null = open) for the saved period, relative to $today. */
    public function range(?string $today = null): array
    {
        return self::periodRange($this->period, $this->date_from?->toDateString(), $this->date_to?->toDateString(), $today);
    }

    public static function periodRange(string $period, ?string $from = null, ?string $to = null, ?string $today = null): array
    {
        $t = $today ? \Carbon\Carbon::parse($today) : now();

        return match ($period) {
            'last_7' => [$t->copy()->subDays(6)->toDateString(), $t->toDateString()],
            'last_30' => [$t->copy()->subDays(29)->toDateString(), $t->toDateString()],
            'last_90' => [$t->copy()->subDays(89)->toDateString(), $t->toDateString()],
            'this_month' => [$t->copy()->startOfMonth()->toDateString(), $t->toDateString()],
            'last_month' => [$t->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(), $t->copy()->subMonthNoOverflow()->endOfMonth()->toDateString()],
            'this_year' => [$t->copy()->startOfYear()->toDateString(), $t->toDateString()],
            'last_12m' => [$t->copy()->subMonths(12)->addDay()->toDateString(), $t->toDateString()],
            'custom' => [$from, $to],
            default => [null, null],
        };
    }
}
