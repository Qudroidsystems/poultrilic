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

        // Calculate flock metrics from actual data, not from Flock models
        if ($flockId) {
            // Single flock selected
            $flockMetrics = $this->calculateFlockMetricsFromData($flockId, $dailyEntries);
            $totalBirds = $flockMetrics['totalBirds'];
            $currentBirds = $flockMetrics['currentBirds'];
            $totalMortality = $flockMetrics['totalMortality'];
        } else {
            // All flocks combined
            $allFlockMetrics = [];
            $totalBirds = 0;
            $currentBirds = 0;
            $totalMortality = 0;
            
            foreach ($flocks as $flock) {
                $flockEntries = $dailyEntries->filter(function($entry) use ($flock) {
                    return $entry->weekEntry && $entry->weekEntry->flock_id == $flock->id;
                });
                
                if ($flockEntries->count() > 0) {
                    $metrics = $this->calculateFlockMetricsFromData($flock->id, $flockEntries);
                    $allFlockMetrics[$flock->id] = $metrics;
                    
                    $totalBirds += $metrics['totalBirds'];
                    $currentBirds += $metrics['currentBirds'];
                    $totalMortality += $metrics['totalMortality'];
                }
            }
        }

        // Validate data quality
        $unrealisticEntries = $this->validateEggProduction($dailyEntries);
        $hasDataQualityIssues = count($unrealisticEntries) > 0;

        // Calculate metrics from actual data, not from FlockAnalyticsService
        $metrics = $this->calculateMetricsFromDailyEntries($dailyEntries, $totalBirds, $currentBirds, $totalMortality);

        // Egg production calculations
        $totalEggProductionCrates = $metrics['total_egg_crates'];
        $totalEggProductionPieces = $metrics['total_egg_pieces_remainder'];
        $totalEggProductionTotalPieces = $metrics['total_egg_pieces'];
        $totalEggProduction = $totalEggProductionTotalPieces;

        // Feed calculations
        $totalFeedBags = $metrics['total_feed_bags'];
        $totalFeedKg = $metrics['total_feed_kg'];
        $totalFeedConsumed = $totalFeedBags;

        // Egg Mortality = Broken Eggs
        $totalEggMortality = $metrics['total_broken_eggs'];

        // Total sold eggs
        $totalEggsSoldCrates = $metrics['total_sold_crates'];
        $totalEggsSoldPieces = $metrics['total_sold_pieces_remainder'];
        $totalEggsSoldTotalPieces = $metrics['total_sold_pieces'];
        $totalEggsSold = $totalEggsSoldTotalPieces;

        // Calculate production rate - FIXED CALCULATION
        $avgProductionRate = $this->calculateProductionRate($dailyEntries, $currentBirds);

        // Revenue calculation in Naira
        $totalRevenue = $metrics['total_revenue'];

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
            $weekBirds = 0;
            $entryCount = $weekEntries->count();
            
            foreach ($weekEntries as $entry) {
                $productionData = FlockAnalyticsService::parseEggData($entry->daily_egg_production);
                $salesData = FlockAnalyticsService::parseEggData($entry->daily_sold_egg);
                $weekProduction += $productionData['total_pieces'];
                $weekSales += $salesData['total_pieces'];
                $weekBroken += $entry->broken_egg;
                $weekBirds += $entry->current_birds;
            }
            
            // Calculate average birds for the week
            $avgWeekBirds = $entryCount > 0 ? $weekBirds / $entryCount : 0;

            // Calculate production rate for the week
            $weekProductionRate = 0;
            if ($avgWeekBirds > 0 && $weekProduction > 0) {
                $avgEggsPerBirdPerDay = ($weekProduction / $entryCount) / $avgWeekBirds;
                $weekProductionRate = min(100, max(0, $avgEggsPerBirdPerDay * 100));
            }

            return [
                'feed_bags' => $weekEntries->sum('daily_feeds'),
                'drugs' => $weekEntries->where('drugs', '!=', 'Nil')->where('drugs', '!=', '')->count(),
                'eggs_produced' => $weekProduction,
                'eggs_sold' => $weekSales,
                'eggs_broken' => $weekBroken,
                'production_rate' => $weekProductionRate,
                'egg_mortality' => $weekBroken,
                'avg_birds' => $avgWeekBirds,
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
                'eggs_sold' => 0, 'eggs_broken' => 0, 'production_rate' => 0, 
                'egg_mortality' => 0, 'avg_birds' => 0
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
        $feedEfficiency = $totalEggProductionTotalPieces > 0 ? $totalFeedBags / ($totalEggProductionTotalPieces / self::EGGS_PER_CRATE) : 0;
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

        // Get flock ages
        $flockAges = [];
        $selectedFlock = null;
        
        if ($flockId) {
            $selectedFlock = Flock::find($flockId);
            $flockAges[$flockId] = $this->calculateFlockAge($flockId);
        } else {
            foreach ($flocks as $flock) {
                $flockAges[$flock->id] = $this->calculateFlockAge($flock->id);
            }
        }

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
            'avgDailyBirds',
            'selectedFlock',
            'flockAges'
        ));
    }

    /**
     * Calculate flock metrics from actual daily entries data
     */
    private function calculateFlockMetricsFromData($flockId, $dailyEntries)
    {
        // Get entries for this specific flock
        $flockEntries = $dailyEntries->filter(function($entry) use ($flockId) {
            return $entry->weekEntry && $entry->weekEntry->flock_id == $flockId;
        });
        
        if ($flockEntries->isEmpty()) {
            return ['totalBirds' => 0, 'currentBirds' => 0, 'totalMortality' => 0];
        }
        
        // Find initial bird count from earliest entry
        $earliestEntry = $flockEntries->sortBy('created_at')->first();
        $totalBirds = $earliestEntry->current_birds ?? 0;
        
        // Find current bird count from latest entry
        $latestEntry = $flockEntries->sortByDesc('created_at')->first();
        $currentBirds = $latestEntry->current_birds ?? 0;
        
        // Calculate mortality
        $totalMortality = max(0, $totalBirds - $currentBirds);
        
        return compact('totalBirds', 'currentBirds', 'totalMortality');
    }

    /**
     * Calculate metrics directly from daily entries
     */
    private function calculateMetricsFromDailyEntries($dailyEntries, $totalBirds, $currentBirds, $totalMortality)
    {
        $totalEggPieces = 0;
        $totalSoldPieces = 0;
        $totalBrokenEggs = 0;
        $totalFeedBags = 0;
        
        foreach ($dailyEntries as $entry) {
            $eggData = FlockAnalyticsService::parseEggData($entry->daily_egg_production);
            $soldData = FlockAnalyticsService::parseEggData($entry->daily_sold_egg);
            
            $totalEggPieces += $eggData['total_pieces'];
            $totalSoldPieces += $soldData['total_pieces'];
            $totalBrokenEggs += $entry->broken_egg ?? 0;
            $totalFeedBags += $entry->daily_feeds ?? 0;
        }
        
        $cratesProduced = floor($totalEggPieces / self::EGGS_PER_CRATE);
        $piecesProduced = $totalEggPieces % self::EGGS_PER_CRATE;
        
        $cratesSold = floor($totalSoldPieces / self::EGGS_PER_CRATE);
        $piecesSold = $totalSoldPieces % self::EGGS_PER_CRATE;
        
        $revenue = $totalSoldPieces * self::EGG_PRICE_NAIRA;
        $totalFeedKg = $totalFeedBags * self::BAG_WEIGHT_KG;
        
        return [
            'total_egg_pieces' => $totalEggPieces,
            'total_egg_crates' => $cratesProduced,
            'total_egg_pieces_remainder' => $piecesProduced,
            'total_sold_pieces' => $totalSoldPieces,
            'total_sold_crates' => $cratesSold,
            'total_sold_pieces_remainder' => $piecesSold,
            'total_broken_eggs' => $totalBrokenEggs,
            'total_feed_bags' => $totalFeedBags,
            'total_feed_kg' => $totalFeedKg,
            'total_revenue' => $revenue,
            'totalBirds' => $totalBirds,
            'currentBirds' => $currentBirds,
            'totalMortality' => $totalMortality,
        ];
    }

    /**
     * Calculate flock age in weeks
     */
    private function calculateFlockAge($flockId)
    {
        $firstEntry = DailyEntry::whereHas('weekEntry', function($q) use ($flockId) {
            $q->where('flock_id', $flockId);
        })->orderBy('created_at', 'asc')->first();
        
        if (!$firstEntry) {
            return 0;
        }
        
        $firstDate = Carbon::parse($firstEntry->created_at);
        $now = Carbon::now();
        
        return $firstDate->diffInWeeks($now);
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
                        'date' => $entry->created_at->format('Y-m-d'),
                        'flock_id' => $entry->weekEntry->flock_id ?? null
                    ];
                }
            }
        }
        
        return $unrealisticEntries;
    }

    /**
     * Calculate average daily production per bird - FIXED VERSION
     */
    private function calculateProductionRate($dailyEntries, $currentBirds)
    {
        if ($currentBirds <= 0 || $dailyEntries->count() === 0) {
            return 0;
        }

        // Get entries with positive birds and valid egg data
        $validEntries = $dailyEntries->filter(function($entry) {
            return $entry->current_birds > 0;
        });
        
        if ($validEntries->count() === 0) {
            return 0;
        }

        // Calculate total eggs from valid entries
        $totalEggs = 0;
        $daysWithProduction = 0;
        
        foreach ($validEntries as $entry) {
            $eggData = FlockAnalyticsService::parseEggData($entry->daily_egg_production);
            
            if ($eggData['total_pieces'] > 0) {
                $totalEggs += $eggData['total_pieces'];
                $daysWithProduction++;
            }
        }
        
        if ($daysWithProduction === 0) {
            return 0;
        }
        
        // Calculate average birds during production days
        $productionEntries = $validEntries->filter(function($entry) {
            $eggData = FlockAnalyticsService::parseEggData($entry->daily_egg_production);
            return $eggData['total_pieces'] > 0;
        });
        
        if ($productionEntries->count() === 0) {
            return 0;
        }
        
        $avgBirdsDuringProduction = $productionEntries->avg('current_birds');
        
        if ($avgBirdsDuringProduction <= 0) {
            return 0;
        }
        
        // Calculate production rate
        $avgEggsPerBirdPerDay = ($totalEggs / $daysWithProduction) / $avgBirdsDuringProduction;
        
        // Return as percentage
        return min(100, $avgEggsPerBirdPerDay * 100);
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
        // Get daily entries for metrics
        $dailyEntries = DailyEntry::whereBetween('created_at', [$startDate, $endDate])
            ->with('weekEntry.flock');
            
        if ($flockId) {
            $dailyEntries->whereHas('weekEntry', function($q) use ($flockId) {
                $q->where('flock_id', $flockId);
            });
        }
        
        $entries = $dailyEntries->get();
        
        // Calculate metrics from data
        $metrics = $this->calculateMetricsFromDailyEntries(
            $entries, 
            0, 0, 0 // These will be calculated from data
        );
        
        // Calculate flock metrics
        $flockMetrics = $this->calculateFlockMetricsFromData($flockId, $entries);
        
        // Merge metrics
        $summaryMetrics = array_merge($metrics, $flockMetrics);

        // Get flock details
        $flocks = Flock::all();
        $selectedFlock = $flockId ? Flock::find($flockId) : null;

        return [
            'pagetitle' => 'Poultry Analytics Report',
            
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
            
            'studentdata' => collect([(object)[
                'firstname' => $selectedFlock ? 'Flock ' . $selectedFlock->name : 'All Flocks',
                'lastname' => '',
                'admissionNo' => $selectedFlock ? 'Flock-' . $selectedFlock->id : 'ALL-FLOCKS',
                'schoolclass' => 'Poultry Farm',
                'arm' => '',
                'homeadd' => 'PrimeFarm Poultry Management',
                'phone' => 'N/A',
            ]]),
            
            'totalSchoolBill' => 0,
            'totalPaid' => 0,
            'totalOutstanding' => 0,
            
            'summaryMetrics' => $summaryMetrics,
            
            'studentpaymentbill' => $this->preparePoultryDataTable($data),
            
            'flocks' => $flocks,
            'selectedFlock' => $selectedFlock,
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