<?php

namespace App\Exports;

use App\Models\DailyEntry;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Carbon\Carbon;

class PoultryAnalyticsExport implements FromCollection, WithHeadings
{
    protected $startDate;
    protected $endDate;
    protected $flockId;

    public function __construct($startDate, $endDate, $flockId = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->flockId = $flockId;
    }

    public function collection()
    {
        $query = $this->flockId
            ? DailyEntry::whereHas('weekEntry', fn($q) => $q->where('flock_id', $this->flockId))
            : DailyEntry::query();

        return $query->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->select([
                'created_at',
                'total_feeds_consumed',
                'drugs',
                'daily_egg_production',
                'total_sold_egg',
                \DB::raw('daily_egg_production / NULLIF(current_birds, 0) * 100 as production_rate'),
                'broken_egg',
                'daily_mortality',
                'current_birds'
            ])
            ->get()
            ->map(function ($entry) {
                return [
                    'Date' => $entry->created_at->format('Y-m-d'),
                    'Feed Consumed (kg)' => $entry->total_feeds_consumed,
                    'Drug Usage (Units)' => $entry->drugs,
                    'Egg Production' => $entry->daily_egg_production,
                    'Eggs Sold' => $entry->total_sold_egg,
                    'Production Rate (%)' => number_format($entry->production_rate, 2),
                    'Egg Mortality' => $entry->broken_egg,
                    'Bird Mortality' => $entry->daily_mortality,
                    'Current Birds' => $entry->current_birds,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Date',
            'Feed Consumed (kg)',
            'Drug Usage (Units)',
            'Egg Production',
            'Eggs Sold',
            'Production Rate (%)',
            'Egg Mortality',
            'Bird Mortality',
            'Current Birds'
        ];
    }
}
