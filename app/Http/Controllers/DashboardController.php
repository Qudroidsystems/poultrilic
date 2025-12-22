<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Flock;
use App\Models\WeekEntry;
use App\Models\DailyEntry;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PoultryAnalyticsExport;
use App\Services\FlockAnalyticsService;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:dashboard', ['only' => ['index', 'export']]);
    }

    // Constants
    const EGGS_PER_CRATE = 30;
    const BAG_WEIGHT_KG = 50;
    const EGG_PRICE_NAIRA = 100; // ₦100 per egg (example price)
    const FEED_COST_PER_BAG = 15000; // ₦15,000 per bag (example price)
    const BIRD_COST = 2000; // ₦2,000 per bird (example cost)
    const DRUG_COST_PER_DAY = 5000; // ₦5,000 per drug administration
    const DAILY_LABOR_COST = 10000; // ₦10,000 per day

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
        $query = DailyEntry::whereBetween('created_at', [$startDate, $endDate])
                          ->with('weekEntry.flock');
        
        if ($flockId) {
            $query->whereHas('weekEntry', function($q) use ($flockId) {
                $q->where('flock_id', $flockId);
            });
        }

        // Get all daily entries for calculations
        $dailyEntries = $query->get();

        // Validate data quality
        $unrealisticEntries = $this->validateEggProduction($dailyEntries);
        $hasDataQualityIssues = count($unrealisticEntries) > 0;

        // Calculate flock metrics using service
        $flockMetrics = FlockAnalyticsService::calculateMetrics($flockId, $startDate, $endDate);
        $totalBirds = $flockMetrics['totalBirds'];
        $currentBirds = $flockMetrics['currentBirds'];
        $totalMortality = $flockMetrics['totalMortality'];

        // Egg production calculations - using service data
        $totalEggProductionCrates = $flockMetrics['total_egg_crates'];
        $totalEggProductionPieces = $flockMetrics['total_egg_pieces_remainder'];
        $totalEggProductionTotalPieces = $flockMetrics['total_egg_pieces'];
        $totalEggProduction = $totalEggProductionTotalPieces;

        // Feed calculations
        $totalFeedBags = $flockMetrics['total_feed_bags'];
        $totalFeedKg = $flockMetrics['total_feed_kg'];
        $totalFeedConsumed = $totalFeedBags;

        // Egg Mortality = Broken Eggs
        $totalEggMortality = $flockMetrics['total_broken_eggs'];

        // Total sold eggs
        $totalEggsSoldCrates = $flockMetrics['total_sold_crates'];
        $totalEggsSoldPieces = $flockMetrics['total_sold_pieces_remainder'];
        $totalEggsSoldTotalPieces = $flockMetrics['total_sold_pieces'];
        $totalEggsSold = $totalEggsSoldTotalPieces;

        // Calculate production rate
        $avgProductionRate = $this->calculateProductionRate($dailyEntries, $currentBirds);

        // Revenue calculation in Naira
        $totalRevenue = $flockMetrics['total_revenue'];

        // Drug usage - count days with drugs administered
        $totalDrugUsage = $dailyEntries->where('drugs', '!=', 'Nil')
            ->where('drugs', '!=', '')
            ->whereNotNull('drugs')
            ->count();

        // Flock Capital Analysis - in Naira
        $capitalInvestment = $totalBirds * self::BIRD_COST; // Cost per bird
        $feedCost = $totalFeedBags * self::FEED_COST_PER_BAG; // Cost per bag
        $drugCost = $totalDrugUsage * self::DRUG_COST_PER_DAY; // Cost per drug administration
        $daysCount = $startDate->diffInDays($endDate) ?: 30;
        $laborCost = self::DAILY_LABOR_COST * $daysCount; // Daily labor cost
        $operationalExpenses = $feedCost + $drugCost + $laborCost;
        $netIncome = $totalRevenue - $operationalExpenses;
        $capitalValue = $netIncome > 0 ? $netIncome / 0.1 : 0; // 10% cap rate

        // Chart Data - Weekly aggregation
        $chartData = $dailyEntries->groupBy(function($entry) {
            return $entry->created_at->format('Y-W');
        })->map(function($weekEntries) {
            $weekProduction = 0;
            $weekSales = 0;
            $weekBroken = 0;
            
            foreach ($weekEntries as $entry) {
                $productionData = FlockAnalyticsService::parseEggData($entry->daily_egg_production);
                $salesData = FlockAnalyticsService::parseEggData($entry->daily_sold_egg);
                $weekProduction += $productionData['total_pieces'];
                $weekSales += $salesData['total_pieces'];
                $weekBroken += $entry->broken_egg;
            }

            // Calculate average production rate for the week
            $weekProductionRate = 0;
            $validWeekEntries = $weekEntries->filter(function($entry) {
                return $entry->current_birds > 0;
            });
            
            if ($validWeekEntries->count() > 0) {
                $avgCurrentBirdsWeek = $validWeekEntries->avg('current_birds');
                if ($avgCurrentBirdsWeek > 0 && $weekProduction > 0) {
                    $avgEggsPerBirdPerDay = ($weekProduction / $validWeekEntries->count()) / $avgCurrentBirdsWeek;
                    $weekProductionRate = min(100, max(0, $avgEggsPerBirdPerDay * 100));
                }
            }

            return [
                'feed_bags' => $weekEntries->sum('daily_feeds'),
                'drugs' => $weekEntries->where('drugs', '!=', 'Nil')->where('drugs', '!=', '')->count(),
                'eggs_produced' => $weekProduction,
                'eggs_sold' => $weekSales,
                'eggs_broken' => $weekBroken,
                'production_rate' => $weekProductionRate,
                'egg_mortality' => $weekBroken,
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
                'eggs_sold' => 0, 'eggs_broken' => 0, 'production_rate' => 0, 'egg_mortality' => 0
            ];
            
            $feedChartData[] = $data['feed_bags'];
            $drugChartData[] = $data['drugs'];
            $eggProductionChartData[] = $data['eggs_produced'];
            $eggSoldChartData[] = $data['eggs_sold'];
            $productionRateChartData[] = $data['production_rate'];
            $eggMortalityChartData[] = $data['egg_mortality'];
        }

        // Calculate egg mortality rate (broken eggs as percentage of total production)
        $eggMortalityRate = 0;
        if ($totalEggProductionTotalPieces > 0) {
            $eggMortalityRate = ($totalEggMortality / $totalEggProductionTotalPieces) * 100;
        }

        // Calculate additional KPIs
        $birdMortalityRate = $totalBirds > 0 ? ($totalMortality / $totalBirds) * 100 : 0;
        $revenuePerBird = $currentBirds > 0 ? $totalRevenue / $currentBirds : 0;
        $feedPerBird = $currentBirds > 0 ? $totalFeedBags / $currentBirds : 0;
        $feedEfficiency = $totalEggProductionTotalPieces > 0 ? $totalFeedBags / $totalEggProductionTotalPieces : 0;
        $costPerEgg = $totalEggsSoldTotalPieces > 0 ? $operationalExpenses / $totalEggsSoldTotalPieces : 0;
        $eggDisposalRate = $totalEggProductionTotalPieces > 0 ? (($totalEggsSoldTotalPieces + $totalEggMortality) / $totalEggProductionTotalPieces) * 100 : 0;
        $eggSalesEfficiency = ($totalEggsSoldTotalPieces + $totalEggMortality) > 0 ? ($totalEggsSoldTotalPieces / ($totalEggsSoldTotalPieces + $totalEggMortality)) * 100 : 0;

        // Data quality metrics
        $daysWithProduction = $dailyEntries->filter(function($entry) {
            $eggData = FlockAnalyticsService::parseEggData($entry->daily_egg_production);
            return $eggData['total_pieces'] > 0;
        })->count();
        
        $avgDailyProduction = $daysWithProduction > 0 ? $totalEggProductionTotalPieces / $daysWithProduction : 0;
        
        // Calculate average daily birds from entries with positive bird count
        $entriesWithBirds = $dailyEntries->filter(function($entry) {
            return $entry->current_birds > 0;
        });
        $avgDailyBirds = $entriesWithBirds->count() > 0 ? $entriesWithBirds->avg('current_birds') : 0;

        return view('dashboards.dashboard', compact(
            'pagetitle',
            'totalBirds',
            'currentBirds',
            'totalMortality',
            'totalEggProduction',
            'totalEggProductionCrates',
            'totalEggProductionPieces',
            'totalEggProductionTotalPieces',
            'totalFeedBags',
            'totalFeedKg',
            'totalFeedConsumed',
            'totalDrugUsage',
            'totalEggsSoldCrates',
            'totalEggsSoldPieces',
            'totalEggsSoldTotalPieces',
            'totalEggsSold',
            'totalRevenue',
            'avgProductionRate',
            'totalEggMortality',
            'eggMortalityRate',
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
            'laborCost',
            'birdMortalityRate',
            'revenuePerBird',
            'feedPerBird',
            'feedEfficiency',
            'costPerEgg',
            'eggDisposalRate',
            'eggSalesEfficiency',
            'hasDataQualityIssues',
            'unrealisticEntries',
            'daysWithProduction',
            'avgDailyProduction',
            'avgDailyBirds'
        ));
    }

    /**
     * Validate and clean egg production data
     */
    private function validateEggProduction($dailyEntries)
    {
        $unrealisticEntries = [];
        
        foreach ($dailyEntries as $entry) {
            if ($entry->current_birds > 0) {
                $eggData = FlockAnalyticsService::parseEggData($entry->daily_egg_production);
                $maxPossible = $entry->current_birds * 1.1; // Allow 10% margin
                
                // If production exceeds possible maximum, flag it
                if ($eggData['total_pieces'] > $maxPossible) {
                    $unrealisticEntries[] = [
                        'id' => $entry->id,
                        'birds' => $entry->current_birds,
                        'eggs' => $eggData['total_pieces'],
                        'rate' => ($eggData['total_pieces'] / $entry->current_birds) * 100,
                        'date' => $entry->created_at->format('Y-m-d')
                    ];
                }
            }
        }
        
        return $unrealisticEntries;
    }

    /**
     * Calculate average daily production per bird
     */
    private function calculateProductionRate($dailyEntries, $currentBirds)
    {
        if ($currentBirds <= 0 || $dailyEntries->count() === 0) {
            return 0;
        }

        // Filter out entries with 0 or negative birds and unrealistic production
        $validEntries = $dailyEntries->filter(function($entry) {
            if ($entry->current_birds <= 0) {
                return false;
            }
            
            $eggData = FlockAnalyticsService::parseEggData($entry->daily_egg_production);
            
            // Cap unrealistic production at 110% of bird count
            $maxPossible = $entry->current_birds * 1.1;
            if ($eggData['total_pieces'] > $maxPossible) {
                return false;
            }
            
            return true;
        });
        
        if ($validEntries->count() === 0) {
            return 0;
        }

        // Get total eggs produced from valid entries only
        $totalEggs = 0;
        $daysWithProduction = 0;
        
        foreach ($validEntries as $entry) {
            $eggData = FlockAnalyticsService::parseEggData($entry->daily_egg_production);
            
            // Only count days with egg production data
            if ($eggData['total_pieces'] > 0) {
                $totalEggs += $eggData['total_pieces'];
                $daysWithProduction++;
            }
        }
        
        if ($daysWithProduction === 0) {
            return 0;
        }
        
        // Use average birds across valid entries
        $avgBirds = $validEntries->avg('current_birds');
        
        if ($avgBirds <= 0) {
            return 0;
        }
        
        // Calculate average eggs per bird per day
        $avgEggsPerBirdPerDay = ($totalEggs / $daysWithProduction) / $avgBirds;
        
        // Cap at 100% (1 egg per bird per day is 100%)
        $productionRate = min(100, max(0, $avgEggsPerBirdPerDay * 100));
        
        return $productionRate;
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

        // Get data for export
        $data = (new PoultryAnalyticsExport($startDate, $endDate, $flockId))->collection()->toArray();
        
        if ($format === 'pdf') {
            // Prepare data for PDF using your template structure
            $exportData = $this->prepareExportData($startDate, $endDate, $flockId, $data);
            
            $pdf = Pdf::loadView('exports.poultry_analytics_pdf', $exportData)
                ->setPaper('a4', 'landscape')
                ->setOptions([
                    'defaultFont' => 'Helvetica',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                ]);
            
            $filename = 'poultry_analytics_' . now()->format('Ymd_His') . '.pdf';
            return $pdf->download($filename);
        }

        // CSV export remains the same
        return Excel::download(
            new PoultryAnalyticsExport($startDate, $endDate, $flockId),
            'poultry_analytics_' . now()->format('Ymd_His') . '.csv'
        );
    }

    /**
     * Prepare data for PDF export using the provided template structure
     */
    private function prepareExportData($startDate, $endDate, $flockId, $data)
    {
        // Calculate flock metrics using service
        $flockMetrics = FlockAnalyticsService::calculateMetrics($flockId, $startDate, $endDate);
        
        // Calculate summary metrics from data
        $summaryMetrics = $this->calculateExportSummary($data, $flockId);
        
        // Merge flock metrics into summary
        $summaryMetrics = array_merge($summaryMetrics, [
            'totalBirds' => $flockMetrics['totalBirds'],
            'currentBirds' => $flockMetrics['currentBirds'],
            'totalMortality' => $flockMetrics['totalMortality'],
        ]);

        // Get selected flock if any
        $selectedFlock = $flockId ? Flock::find($flockId) : null;
        
        // Prepare the data array matching your template structure
        return [
            'pagetitle' => 'Poultry Analytics Report',
            
            // Mock school info structure for your template
            'schoolInfo' => (object)[
                'logo_url' => null,
                'school_name' => 'Poultry Farm Analytics',
                'school_address' => 'PrimeFarm Poultry Management System',
                'school_email' => 'analytics@primefarm.ng',
                'school_phone' => '+234 XXX XXX XXXX',
            ],
            
            'statementNumber' => 'PA-' . now()->format('Ymd-His'),
            'schoolterm' => 'Poultry Analytics',
            'schoolsession' => $startDate->format('M Y') . ' to ' . $endDate->format('M Y'),
            
            // Student info structure (adapted for flock info)
            'studentdata' => collect([(object)[
                'firstname' => $selectedFlock ? 'Flock ' . $selectedFlock->name : 'All Flocks',
                'lastname' => '',
                'admissionNo' => $selectedFlock ? 'Flock-' . $selectedFlock->id : 'ALL-FLOCKS',
                'schoolclass' => 'Poultry Farm',
                'arm' => '',
                'homeadd' => 'PrimeFarm Poultry Management',
                'phone' => 'N/A',
            ]]),
            
            // Summary metrics
            'totalSchoolBill' => 0, // Not applicable for poultry
            'totalPaid' => 0, // Not applicable for poultry
            'totalOutstanding' => 0, // Not applicable for poultry
            
            // Poultry summary metrics
            'summaryMetrics' => $summaryMetrics,
            
            // Main data table
            'studentpaymentbill' => $this->preparePoultryDataTable($data),
            
            // Date range
            'startDate' => $startDate,
            'endDate' => $endDate,
            'flockId' => $flockId,
        ];
    }

    /**
     * Calculate summary metrics for export
     */
    private function calculateExportSummary($data, $flockId = null)
    {
        if (empty($data)) {
            return [
                'totalEggProduction' => 0,
                'totalEggsSold' => 0,
                'totalRevenue' => 0,
                'totalFeedBags' => 0,
                'totalDrugUsage' => 0,
                'avgProductionRate' => 0,
                'netIncome' => 0,
            ];
        }
        
        // Calculate totals from data
        $totalEggProduction = 0;
        $totalEggsSold = 0;
        $totalFeedBags = 0;
        $totalDrugUsage = 0;
        $totalRevenue = 0;
        
        foreach ($data as $row) {
            $totalEggProduction += $row['total_egg_production'] ?? 0;
            $totalEggsSold += $row['eggs_sold'] ?? 0;
            $totalFeedBags += $row['feed_consumed_bags'] ?? 0;
            $totalDrugUsage += $row['drug_usage_days'] ?? 0;
            $totalRevenue += $row['revenue'] ?? 0;
        }
        
        return [
            'totalEggProduction' => $totalEggProduction,
            'totalEggsSold' => $totalEggsSold,
            'totalRevenue' => $totalRevenue,
            'totalFeedBags' => $totalFeedBags,
            'totalDrugUsage' => $totalDrugUsage,
            'avgProductionRate' => $data[0]['production_rate'] ?? 0,
            'netIncome' => $data[0]['net_income'] ?? 0,
        ];
    }

    /**
     * Prepare poultry data for the table in your template
     */
    private function preparePoultryDataTable($data)
    {
        $tableData = [];
        
        foreach ($data as $index => $row) {
            $tableData[] = (object)[
                'title' => $row['week'] ?? 'N/A',
                'description' => 'Weekly Analytics',
                'amount' => $row['total_egg_production'] ?? 0,
                'amount_paid' => $row['eggs_sold'] ?? 0,
                'balance' => ($row['total_egg_production'] ?? 0) - ($row['eggs_sold'] ?? 0),
                'payment_method' => 'Egg Production',
                'payment_date' => $row['date'] ?? now()->format('Y-m-d'),
                'payment_status' => $row['production_rate'] >= 70 ? 'Good' : ($row['production_rate'] >= 50 ? 'Average' : 'Poor'),
                'received_by' => 'System',
            ];
        }
        
        return collect($tableData);
    }
}