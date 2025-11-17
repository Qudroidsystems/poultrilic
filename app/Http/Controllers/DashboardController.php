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

    // Constants
    const EGGS_PER_CRATE = 30;
    const KG_PER_BAG = 50;

    /**
     * Parse egg string format (e.g., "25 Cr 19PC") to total pieces and crates
     */
    private function parseEggData($eggString)
    {
        if (empty($eggString) || $eggString === '0 Cr 0PC' || $eggString === '0 Cr 0PC') {
            return ['crates' => 0, 'pieces' => 0, 'total_pieces' => 0];
        }

        try {
            // Handle formats like "25 Cr 19PC", "0 Cr 5PC", "25 Cr 0PC", "4,985 CR"
            if (strpos($eggString, ',') !== false) {
                // Handle comma format like "4,985 CR"
                preg_match('/([\d,]+)\s*CR?/', $eggString, $matches);
                if (count($matches) === 2) {
                    $crates = (int)str_replace(',', '', $matches[1]);
                    return [
                        'crates' => $crates,
                        'pieces' => 0,
                        'total_pieces' => $crates * self::EGGS_PER_CRATE
                    ];
                }
            } else {
                // Handle regular format "25 Cr 19PC"
                preg_match('/(\d+)\s*Cr\s*(\d+)PC/', $eggString, $matches);
                if (count($matches) === 3) {
                    $crates = (int)$matches[1];
                    $pieces = (int)$matches[2];
                    return [
                        'crates' => $crates,
                        'pieces' => $pieces,
                        'total_pieces' => ($crates * self::EGGS_PER_CRATE) + $pieces
                    ];
                }
                
                // Handle format with only crates "21CR"
                preg_match('/(\d+)\s*CR/', $eggString, $matches);
                if (count($matches) === 2) {
                    $crates = (int)$matches[1];
                    return [
                        'crates' => $crates,
                        'pieces' => 0,
                        'total_pieces' => $crates * self::EGGS_PER_CRATE
                    ];
                }
            }
            
            return ['crates' => 0, 'pieces' => 0, 'total_pieces' => 0];
        } catch (\Exception $e) {
            return ['crates' => 0, 'pieces' => 0, 'total_pieces' => 0];
        }
    }

    /**
     * Convert kg to bags
     */
    private function kgToBags($kg)
    {
        return $kg / self::KG_PER_BAG;
    }

    /**
     * Convert bags to kg
     */
    private function bagsToKg($bags)
    {
        return $bags * self::KG_PER_BAG;
    }

    /**
     * Get total sold eggs from the most recent entry (cumulative total)
     */
    private function getTotalSoldEggs($dailyEntries)
    {
        if ($dailyEntries->count() === 0) {
            return ['crates' => 0, 'pieces' => 0, 'total_pieces' => 0];
        }

        // Get the most recent entry which should have the cumulative total_sold_egg
        $latestEntry = $dailyEntries->sortByDesc('created_at')->first();
        
        if ($latestEntry && $latestEntry->total_sold_egg) {
            return $this->parseEggData($latestEntry->total_sold_egg);
        }

        return ['crates' => 0, 'pieces' => 0, 'total_pieces' => 0];
    }

    /**
     * Calculate total production from daily entries
     */
    private function calculateTotalProduction($dailyEntries)
    {
        $totalCrates = 0;
        $totalPieces = 0;
        $totalPiecesOnly = 0;

        foreach ($dailyEntries as $entry) {
            $eggData = $this->parseEggData($entry->daily_egg_production);
            $totalCrates += $eggData['crates'];
            $totalPieces += $eggData['pieces'];
            $totalPiecesOnly += $eggData['total_pieces'];
        }

        // Convert excess pieces to crates
        $extraCratesFromPieces = floor($totalPieces / self::EGGS_PER_CRATE);
        $finalCrates = $totalCrates + $extraCratesFromPieces;
        $finalPieces = $totalPieces % self::EGGS_PER_CRATE;

        return [
            'crates' => $finalCrates,
            'pieces' => $finalPieces,
            'total_pieces' => $totalPiecesOnly
        ];
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

        // Egg production calculations - in crates and pieces
        $productionData = $this->calculateTotalProduction($dailyEntries);
        $totalEggProductionCrates = $productionData['crates'];
        $totalEggProductionPieces = $productionData['pieces'];
        $totalEggProductionTotalPieces = $productionData['total_pieces'];

        // Feed calculations - convert to bags
        $totalFeedKg = $dailyEntries->sum('daily_feeds');
        $totalFeedBags = $this->kgToBags($totalFeedKg);

        $totalMortality = $dailyEntries->sum('daily_mortality');
        $totalEggMortality = $dailyEntries->sum('broken_egg');

        // CORRECTED: Get total sold eggs from cumulative total_sold_egg field of latest entry
        $soldEggData = $this->getTotalSoldEggs($dailyEntries);
        $totalEggsSoldCrates = $soldEggData['crates'];
        $totalEggsSoldPieces = $soldEggData['pieces'];
        $totalEggsSoldTotalPieces = $soldEggData['total_pieces'];

        // Production rate calculation
        $avgProductionRate = 0;
        if ($currentBirds > 0 && $dailyEntries->count() > 0) {
            $totalProductionDays = $dailyEntries->count();
            $avgDailyProduction = $totalEggProductionTotalPieces / $totalProductionDays;
            $avgProductionRate = ($avgDailyProduction / $currentBirds) * 100;
        }

        // Revenue calculation (assuming $0.05 per egg)
        $totalRevenue = $totalEggsSoldTotalPieces * 0.05;

        // Drug usage - count days with drugs administered
        $totalDrugUsage = $dailyEntries->where('drugs', '!=', 'Nil')
            ->where('drugs', '!=', '')
            ->whereNotNull('drugs')
            ->count();

        // Flock Capital Analysis - using bags for feed cost
        $capitalInvestment = $totalBirds * 2; // $2 per bird
        $feedCost = $totalFeedBags * 25; // $25 per bag (50kg)
        $drugCost = $totalDrugUsage * 10; // $10 per drug administration
        $laborCost = 1000; // Fixed for 30 days
        $operationalExpenses = $feedCost + $drugCost + $laborCost;
        $netIncome = $totalRevenue - $operationalExpenses;
        $capitalValue = $netIncome > 0 ? $netIncome / 0.1 : 0; // 10% cap rate

        // Chart Data - Weekly aggregation
        $chartData = $dailyEntries->groupBy(function($entry) {
            return $entry->created_at->format('Y-W');
        })->map(function($weekEntries) {
            $weekProduction = 0;
            $weekSales = 0;
            
            foreach ($weekEntries as $entry) {
                $productionData = $this->parseEggData($entry->daily_egg_production);
                $salesData = $this->parseEggData($entry->daily_sold_egg);
                $weekProduction += $productionData['total_pieces'];
                $weekSales += $salesData['total_pieces'];
            }

            return [
                'feed_bags' => $this->kgToBags($weekEntries->sum('daily_feeds')),
                'drugs' => $weekEntries->where('drugs', '!=', 'Nil')->where('drugs', '!=', '')->count(),
                'eggs_produced' => $weekProduction,
                'eggs_sold' => $weekSales,
                'production_rate' => $weekEntries->avg(function($entry) {
                    $production = $this->parseEggData($entry->daily_egg_production)['total_pieces'];
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
                'feed_bags' => 0, 'drugs' => 0, 'eggs_produced' => 0, 
                'eggs_sold' => 0, 'production_rate' => 0, 'egg_mortality' => 0
            ];
            
            $feedChartData[] = $data['feed_bags'];
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
            'totalEggProductionCrates',
            'totalEggProductionPieces',
            'totalEggProductionTotalPieces',
            'totalMortality',
            'totalFeedBags',
            'totalFeedKg',
            'totalDrugUsage',
            'totalEggsSoldCrates',
            'totalEggsSoldPieces',
            'totalEggsSoldTotalPieces',
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
            'eggMortalityChartData',
            'feedCost',
            'drugCost',
            'laborCost'
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