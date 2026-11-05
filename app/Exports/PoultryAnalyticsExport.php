<?php

namespace App\Exports;

use App\Support\FarmSettings;
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
            ->betweenDates($this->startDate, $this->endDate)
            ->oldestFirst();

        if ($this->flockId) {
            $query->forFlock($this->flockId);
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
        $eggsProduced = (int) $entry->eggs_produced;
        $eggsSold = (int) $entry->eggs_sold;
        $revenue = $eggsSold * FarmSettings::eggPrice();

        // Calculate production rate for this day
        $productionRate = 0;
        if ($entry->current_birds > 0 && $eggsProduced > 0) {
            $productionRate = min(100, ($eggsProduced / $entry->current_birds) * 100);
        }

        return [
            ($entry->entry_date ?? $entry->created_at)->format('Y-m-d'),
            $entry->weekEntry->flock->name ?? 'N/A',
            $entry->current_birds,
            $eggsProduced,
            $eggsSold,
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