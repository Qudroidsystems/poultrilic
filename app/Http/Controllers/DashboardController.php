<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Flock;
use App\Models\WeekEntry;
use App\Models\DailyEntry;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PoultryAnalyticsExport;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:dashboard', ['only' => ['index', 'export']]);
    }

    /**
     * Parse egg string format (e.g., "25 Cr 19PC") to total pieces
     */
    private function parseEggQuantity($eggString)
    {
        if (empty($eggString) || $eggString === '0 Cr 0PC') {
            return 0;
        }

        try {
            // Handle formats like "25 Cr 19PC", "0 Cr 5PC", "25 Cr 0PC"
            preg_match('/(\d+)\s*Cr\s*(\d+)PC/', $eggString, $matches);
            
            if (count($matches) === 3) {
                $crates = (int)$matches[1];
                $pieces = (int)$matches[2];
                return ($crates * 30) + $pieces; // Assuming 30 eggs per crate
            }
            
            return 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Parse egg string to get crates only
     */
    private function parseEggCrates($eggString)
    {
        if (empty($eggString) || $eggString === '0 Cr 0PC') {
            return 0;
        }

        try {
            preg_match('/(\d+)\s*Cr/', $eggString, $matches);
            return count($matches) === 2 ? (int)$matches[1] : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function index(Request $request)
    {
        $pagetitle = "Poultry Analytics";

        // Date range from request or default to last 30 days
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();
        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::now()->endOfDay();

        // Flock filter
        $flockId = $request->input('flock_id');
        $flocks = Flock::all();
        
        // Base query with date filtering
        $query = DailyEntry::whereBetween('created_at', [$startDate, $endDate]);
        
        if ($flockId) {
            $query->whereHas('weekEntry', function($q) use ($flockId) {
                $q->where('flock_id', $flockId);
            });
        }

        // Get all daily entries for calculations
        $dailyEntries = $query->with('weekEntry.flock')->get();

        // Key Metrics Calculations
        $totalBirds = $flockId 
            ? Flock::find($flockId)->initial_bird_count ?? 0
            : Flock::sum('initial_bird_count');

        // Current birds from the most recent entry
        $currentBirds = 0;
        if ($dailyEntries->count() > 0) {
            $latestEntry = $dailyEntries->sortByDesc('created_at')->first();
            $currentBirds = $latestEntry->current_birds ?? 0;
        }

        // Egg production calculations
        $totalEggProduction = $dailyEntries->sum(function($entry) {
            return $this->parseEggQuantity($entry->daily_egg_production);
        });

        $totalEggProductionCrates = $dailyEntries->sum(function($entry) {
            return $this->parseEggCrates($entry->daily_egg_production);
        });

        $totalMortality = $dailyEntries->sum('daily_mortality');
        $totalFeedConsumed = $dailyEntries->sum('daily_feeds'); // Using daily_feeds, not total_feeds_consumed
        $totalEggMortality = $dailyEntries->sum('broken_egg');

        // Egg sales calculations
        $totalEggsSold = $dailyEntries->sum(function($entry) {
            return $this->parseEggQuantity($entry->daily_sold_egg);
        });

        // Production rate calculation
        $avgProductionRate = 0;
        if ($currentBirds > 0 && $dailyEntries->count() > 0) {
            $totalProductionDays = $dailyEntries->count();
            $avgDailyProduction = $totalEggProduction / $totalProductionDays;
            $avgProductionRate = ($avgDailyProduction / $currentBirds) * 100;
        }

        // Revenue calculation (assuming $0.05 per egg)
        $totalRevenue = $totalEggsSold * 0.05;

        // Drug usage - count days with drugs administered
        $totalDrugUsage = $dailyEntries->where('drugs', '!=', 'Nil')
            ->where('drugs', '!=', '')
            ->whereNotNull('drugs')
            ->count();

        // Flock Capital Analysis
        $capitalInvestment = $totalBirds * 2; // $2 per bird
        $feedCost = $totalFeedConsumed * 0.5; // $0.5 per kg
        $drugCost = $totalDrugUsage * 10; // $10 per drug administration
        $laborCost = 1000; // Fixed for 30 days
        $operationalExpenses = $feedCost + $drugCost + $laborCost;
        $netIncome = $totalRevenue - $operationalExpenses;
        $capitalValue = $netIncome > 0 ? $netIncome / 0.1 : 0; // 10% cap rate

        // Chart Data - Weekly aggregation
        $chartData = $dailyEntries->groupBy(function($entry) {
            return $entry->created_at->format('Y-W');
        })->map(function($weekEntries) {
            return [
                'feed' => $weekEntries->sum('daily_feeds'),
                'drugs' => $weekEntries->where('drugs', '!=', 'Nil')->where('drugs', '!=', '')->count(),
                'eggs_produced' => $weekEntries->sum(function($entry) {
                    return $this->parseEggQuantity($entry->daily_egg_production);
                }),
                'eggs_sold' => $weekEntries->sum(function($entry) {
                    return $this->parseEggQuantity($entry->daily_sold_egg);
                }),
                'production_rate' => $weekEntries->avg(function($entry) {
                    $production = $this->parseEggQuantity($entry->daily_egg_production);
                    return $entry->current_birds > 0 ? ($production / $entry->current_birds) * 100 : 0;
                }),
                'egg_mortality' => $weekEntries->sum('broken_egg'),
            ];
        });

        // Get last 4 weeks for chart labels
        $weeks = collect();
        for ($i = 3; $i >= 0; $i--) {
            $weeks->push(Carbon::now()->subWeeks($i)->format('Y-W'));
        }

        // Initialize chart data arrays
        $feedChartData = [];
        $drugChartData = [];
        $eggProductionChartData = [];
        $eggSoldChartData = [];
        $productionRateChartData = [];
        $eggMortalityChartData = [];

        foreach ($weeks as $week) {
            $data = $chartData[$week] ?? [
                'feed' => 0, 'drugs' => 0, 'eggs_produced' => 0, 
                'eggs_sold' => 0, 'production_rate' => 0, 'egg_mortality' => 0
            ];
            
            $feedChartData[] = $data['feed'];
            $drugChartData[] = $data['drugs'];
            $eggProductionChartData[] = $data['eggs_produced'];
            $eggSoldChartData[] = $data['eggs_sold'];
            $productionRateChartData[] = $data['production_rate'];
            $eggMortalityChartData[] = $data['egg_mortality'];
        }

        return view('dashboards.dashboard', compact(
            'pagetitle',
            'totalBirds',
            'currentBirds',
            'totalEggProduction',
            'totalEggProductionCrates',
            'totalMortality',
            'totalFeedConsumed',
            'totalDrugUsage',
            'totalEggsSold',
            'totalRevenue',
            'avgProductionRate',
            'totalEggMortality',
            'capitalInvestment',
            'operationalExpenses',
            'netIncome',
            'capitalValue',
            'flocks',
            'flockId',
            'startDate',
            'endDate',
            'weeks',
            'feedChartData',
            'drugChartData',
            'eggProductionChartData',
            'eggSoldChartData',
            'productionRateChartData',
            'eggMortalityChartData'
        ));
    }

    public function export(Request $request)
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();
        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::now()->endOfDay();
        $flockId = $request->input('flock_id');
        $format = $request->input('format', 'csv');

        if ($format === 'pdf') {
            $data = (new PoultryAnalyticsExport($startDate, $endDate, $flockId))->collection()->toArray();
            $pdf = Pdf::loadView('exports.poultry_analytics_pdf', [
                'data' => $data,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'flockId' => $flockId
            ]);
            return $pdf->download('poultry_analytics_' . now()->format('Ymd_His') . '.pdf');
        }

        return Excel::download(
            new PoultryAnalyticsExport($startDate, $endDate, $flockId),
            'poultry_analytics_' . now()->format('Ymd_His') . '.csv'
        );
    }
}