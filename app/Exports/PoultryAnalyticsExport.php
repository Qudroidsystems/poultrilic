<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Flock;
use App\Models\DailyEntry;
use App\Services\FlockAnalyticsService;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class PoultryAnalyticsExport implements FromCollection, WithHeadings, WithMapping
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
        $query = DailyEntry::with(['weekEntry.flock'])
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);
            
        if ($this->flockId) {
            $query->whereHas('weekEntry', function($q) {
                $q->where('flock_id', $this->flockId);
            });
        }
        
        return $query->get();
    }
    
    public function headings(): array
    {
        return [
            'Date',
            'Flock',
            'Current Birds',
            'Eggs Produced',
            'Eggs Sold',
            'Broken Eggs',
            'Feed Consumed (Bags)',
            'Drugs Administered',
            'Daily Mortality',
            'Production Rate (%)',
            'Revenue (₦)',
            'Notes'
        ];
    }
    
    public function map($entry): array
    {
        $eggProduction = FlockAnalyticsService::parseEggData($entry->daily_egg_production);
        $eggSales = FlockAnalyticsService::parseEggData($entry->daily_sold_egg);
        $revenue = $eggSales['total_pieces'] * FlockAnalyticsService::EGG_PRICE_NAIRA;
        
        // Calculate production rate for this day
        $productionRate = 0;
        if ($entry->current_birds > 0 && $eggProduction['total_pieces'] > 0) {
            $productionRate = min(100, ($eggProduction['total_pieces'] / $entry->current_birds) * 100);
        }
        
        return [
            $entry->created_at->format('Y-m-d'),
            $entry->weekEntry->flock->name ?? 'N/A',
            $entry->current_birds,
            $eggProduction['total_pieces'],
            $eggSales['total_pieces'],
            $entry->broken_egg ?? 0,
            $entry->daily_feeds,
            $entry->drugs != 'Nil' && !empty($entry->drugs) ? 'Yes' : 'No',
            $entry->daily_mortality,
            number_format($productionRate, 1),
            number_format($revenue, 2),
            $entry->drugs != 'Nil' ? $entry->drugs : ''
        ];
    }
}