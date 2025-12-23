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
    const EGG_PRICE_NAIRA = 100;
    const FEED_COST_PER_BAG = 15000;
    const BIRD_COST = 2000;
    const DRUG_COST_PER_DAY = 5000;
    const DAILY_LABOR_COST = 10000;

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
        
        // Separate active and inactive flocks
        $activeFlocks = Flock::where('status', 'active')->get();
        $inactiveFlocks = Flock::where('status', '!=', 'active')->get();
        
        // Get all daily entries for calculations
        $dailyEntries = DailyEntry::whereBetween('created_at', [$startDate, $endDate])
            ->with('weekEntry.flock')
            ->when($flockId, function($query, $flockId) {
                return $query->whereHas('weekEntry', function($q) use ($flockId) {
                    $q->where('flock_id', $flockId);
                });
            })
            ->get();

        // Get active flock entries
        $activeFlockEntries = DailyEntry::whereBetween('created_at', [$startDate, $endDate])
            ->with('weekEntry.flock')
            ->whereHas('weekEntry.flock', function($q) {
                $q->where('status', 'active');
            })
            ->get();

        // Get inactive flock entries
        $inactiveFlockEntries = DailyEntry::whereBetween('created_at', [$startDate, $endDate])
            ->with('weekEntry.flock')
            ->whereHas('weekEntry.flock', function($q) {
                $q->where('status', '!=', 'active');
            })
            ->get();

        // Analyze flock data
        $flockAnalysis = $this->analyzeFlockData($dailyEntries);
        $activeFlockAnalysis = $this->analyzeFlockData($activeFlockEntries);
        $inactiveFlockAnalysis = $this->analyzeFlockData($inactiveFlockEntries);

        // Calculate production metrics
        $productionMetrics = $this->calculateProductionMetrics($dailyEntries);
        $activeProductionMetrics = $this->calculateProductionMetrics($activeFlockEntries);
        $inactiveProductionMetrics = $this->calculateProductionMetrics($inactiveFlockEntries);
        
        $feedMetrics = $this->calculateFeedMetrics($dailyEntries);
        $revenueMetrics = $this->calculateRevenueMetrics($dailyEntries);

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

        // Use appropriate analysis based on selection
        if ($flockId && $selectedFlock) {
            if ($selectedFlock->status === 'active') {
                $totalBirds = $activeFlockAnalysis['flocks'][$flockId]['totalBirds'] ?? $selectedFlock->initial_bird_count;
                $currentBirds = $activeFlockAnalysis['flocks'][$flockId]['currentBirds'] ?? $selectedFlock->initial_bird_count;
                $totalMortality = $activeFlockAnalysis['flocks'][$flockId]['totalMortality'] ?? 0;
            } else {
                $totalBirds = $inactiveFlockAnalysis['flocks'][$flockId]['totalBirds'] ?? $selectedFlock->initial_bird_count;
                $currentBirds = $inactiveFlockAnalysis['flocks'][$flockId]['currentBirds'] ?? $selectedFlock->initial_bird_count;
                $totalMortality = $inactiveFlockAnalysis['flocks'][$flockId]['totalMortality'] ?? 0;
            }
        } else {
            // All flocks combined
            $totalBirds = $flockAnalysis['totalBirdsAll'];
            $currentBirds = $flockAnalysis['currentBirdsAll'];
            $totalMortality = $flockAnalysis['totalMortalityAll'];
        }

        // Validate data quality
        $unrealisticEntries = $this->validateEggProduction($dailyEntries);
        $hasDataQualityIssues = count($unrealisticEntries) > 0;

        // Calculate production rate
        $avgProductionRate = $this->calculateProductionRate($dailyEntries, $currentBirds);

        // Drug usage
        $totalDrugUsage = $dailyEntries->where('drugs', '!=', 'Nil')
            ->where('drugs', '!=', '')
            ->whereNotNull('drugs')
            ->count();

        // Flock Capital Analysis
        $capitalInvestment = $totalBirds * self::BIRD_COST;
        $feedCost = $feedMetrics['total_feed_bags'] * self::FEED_COST_PER_BAG;
        $drugCost = $totalDrugUsage * self::DRUG_COST_PER_DAY;
        $daysCount = $startDate->diffInDays($endDate) ?: 30;
        $laborCost = self::DAILY_LABOR_COST * $daysCount;
        $operationalExpenses = $feedCost + $drugCost + $laborCost;
        $netIncome = $revenueMetrics['total_revenue'] - $operationalExpenses;
        $capitalValue = $netIncome > 0 ? $netIncome / 0.1 : 0;

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
            
            $avgWeekBirds = $entryCount > 0 ? $weekBirds / $entryCount : 0;

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

        // Calculate additional KPIs
        $birdMortalityRate = $totalBirds > 0 ? ($totalMortality / $totalBirds) * 100 : 0;
        $revenuePerBird = $currentBirds > 0 ? $revenueMetrics['total_revenue'] / $currentBirds : 0;
        $feedPerBird = $currentBirds > 0 ? $feedMetrics['total_feed_bags'] / $currentBirds : 0;
        $feedEfficiency = $productionMetrics['total_egg_pieces'] > 0 ? $feedMetrics['total_feed_bags'] / ($productionMetrics['total_egg_pieces'] / self::EGGS_PER_CRATE) : 0;
        $costPerEgg = $productionMetrics['total_sold_pieces'] > 0 ? $operationalExpenses / $productionMetrics['total_sold_pieces'] : 0;
        $eggDisposalRate = $productionMetrics['total_egg_pieces'] > 0 ? (($productionMetrics['total_sold_pieces'] + $productionMetrics['total_broken_eggs']) / $productionMetrics['total_egg_pieces']) * 100 : 0;
        $eggSalesEfficiency = ($productionMetrics['total_sold_pieces'] + $productionMetrics['total_broken_eggs']) > 0 ? ($productionMetrics['total_sold_pieces'] / ($productionMetrics['total_sold_pieces'] + $productionMetrics['total_broken_eggs'])) * 100 : 0;

        // Data quality metrics
        $daysWithProduction = $dailyEntries->filter(function($entry) {
            $eggData = FlockAnalyticsService::parseEggData($entry->daily_egg_production);
            return $eggData['total_pieces'] > 0;
        })->count();
        
        $avgDailyProduction = $daysWithProduction > 0 ? $productionMetrics['total_egg_pieces'] / $daysWithProduction : 0;
        
        // Calculate average daily birds from entries with positive bird count
        $entriesWithBirds = $dailyEntries->filter(function($entry) {
            return $entry->current_birds > 0;
        });
        $avgDailyBirds = $entriesWithBirds->count() > 0 ? $entriesWithBirds->avg('current_birds') : 0;

        // EXTRACT INDIVIDUAL VARIABLES FROM ARRAYS FOR THE VIEW
        $totalEggProductionCrates = $productionMetrics['total_egg_crates'];
        $totalEggProductionPieces = $productionMetrics['total_egg_pieces_remainder'];
        $totalEggProductionTotalPieces = $productionMetrics['total_egg_pieces'];

        $totalFeedBags = $feedMetrics['total_feed_bags'];
        $totalFeedKg = $feedMetrics['total_feed_kg'];
        $totalFeedConsumed = $totalFeedBags;

        $totalEggsSoldCrates = $productionMetrics['total_sold_crates'];
        $totalEggsSoldPieces = $productionMetrics['total_sold_pieces_remainder'];
        $totalEggsSoldTotalPieces = $productionMetrics['total_sold_pieces'];
        $totalEggsSold = $totalEggsSoldTotalPieces;

        $totalRevenue = $revenueMetrics['total_revenue'];

        $totalEggMortality = $productionMetrics['total_broken_eggs'];
        $eggMortalityRate = $productionMetrics['total_egg_pieces'] > 0 ? 
            ($totalEggMortality / $productionMetrics['total_egg_pieces']) * 100 : 0;

        // Prepare data for view
        return view('dashboards.dashboard', compact(
            'pagetitle',
            'totalBirds',
            'currentBirds',
            'totalMortality',
            
            // Production metrics
            'productionMetrics',
            'feedMetrics', 
            'revenueMetrics',
            
            // Individual production variables
            'totalEggProductionCrates',
            'totalEggProductionPieces', 
            'totalEggProductionTotalPieces',
            'totalFeedBags',
            'totalFeedKg',
            'totalFeedConsumed',
            'totalEggsSoldCrates',
            'totalEggsSoldPieces',
            'totalEggsSoldTotalPieces',
            'totalEggsSold',
            'totalRevenue',
            
            'totalDrugUsage',
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
            'daysCount',
            
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
            'flockAges',
            'flockAnalysis',
            
            // Active/Inactive flocks
            'activeFlocks',
            'inactiveFlocks',
            'activeFlockAnalysis',
            'inactiveFlockAnalysis',
            'activeProductionMetrics',
            'inactiveProductionMetrics'
        ));
    }

    /**
     * Analyze flock data from daily entries to get accurate counts
     */
    private function analyzeFlockData($dailyEntries)
    {
        $flocks = [];
        $totalBirdsAll = 0;
        $currentBirdsAll = 0;
        $totalMortalityAll = 0;
        
        // Group entries by flock
        $groupedEntries = $dailyEntries->groupBy(function($entry) {
            return $entry->weekEntry->flock_id ?? 0;
        });
        
        foreach ($groupedEntries as $flockId => $entries) {
            if ($flockId == 0 || $entries->isEmpty()) continue;
            
            $firstEntry = $entries->first();
            $latestEntry = $entries->last();
            
            // Get flock from database
            $flock = Flock::find($flockId);
            if (!$flock) continue;
            
            // Use flock's initial count
            $initialBirds = $flock->initial_bird_count;
            
            // Get current birds from latest entry
            $currentBirds = $latestEntry->current_birds;
            
            // Calculate mortality
            $mortality = max(0, $initialBirds - $currentBirds);
            
            $flocks[$flockId] = [
                'totalBirds' => $initialBirds,
                'currentBirds' => $currentBirds,
                'totalMortality' => $mortality,
                'flock' => $flock,
                'entryCount' => $entries->count(),
                'firstDate' => $firstEntry->created_at->format('Y-m-d'),
                'lastDate' => $latestEntry->created_at->format('Y-m-d')
            ];
            
            $totalBirdsAll += $initialBirds;
            $currentBirdsAll += $currentBirds;
            $totalMortalityAll += $mortality;
        }
        
        return [
            'flocks' => $flocks,
            'totalBirdsAll' => $totalBirdsAll,
            'currentBirdsAll' => $currentBirdsAll,
            'totalMortalityAll' => $totalMortalityAll,
            'flockCount' => count($flocks),
            'totalEntries' => $dailyEntries->count(),
        ];
    }

    /**
     * Calculate production metrics from daily entries
     */
    private function calculateProductionMetrics($dailyEntries)
    {
        $totalEggPieces = 0;
        $totalSoldPieces = 0;
        $totalBrokenEggs = 0;
        
        foreach ($dailyEntries as $entry) {
            $eggData = FlockAnalyticsService::parseEggData($entry->daily_egg_production);
            $soldData = FlockAnalyticsService::parseEggData($entry->daily_sold_egg);
            
            $totalEggPieces += $eggData['total_pieces'];
            $totalSoldPieces += $soldData['total_pieces'];
            $totalBrokenEggs += $entry->broken_egg ?? 0;
        }
        
        $cratesProduced = floor($totalEggPieces / self::EGGS_PER_CRATE);
        $piecesProduced = $totalEggPieces % self::EGGS_PER_CRATE;
        
        $cratesSold = floor($totalSoldPieces / self::EGGS_PER_CRATE);
        $piecesSold = $totalSoldPieces % self::EGGS_PER_CRATE;
        
        return [
            'total_egg_pieces' => $totalEggPieces,
            'total_egg_crates' => $cratesProduced,
            'total_egg_pieces_remainder' => $piecesProduced,
            'total_sold_pieces' => $totalSoldPieces,
            'total_sold_crates' => $cratesSold,
            'total_sold_pieces_remainder' => $piecesSold,
            'total_broken_eggs' => $totalBrokenEggs,
            'egg_production_string' => "{$cratesProduced} Cr {$piecesProduced}PC",
            'egg_sales_string' => "{$cratesSold} Cr {$piecesSold}PC",
        ];
    }
    
    /**
     * Calculate feed metrics
     */
    private function calculateFeedMetrics($dailyEntries)
    {
        $totalFeedBags = $dailyEntries->sum('daily_feeds');
        $totalFeedKg = $totalFeedBags * self::BAG_WEIGHT_KG;
        
        return [
            'total_feed_bags' => $totalFeedBags,
            'total_feed_kg' => $totalFeedKg,
        ];
    }
    
    /**
     * Calculate revenue metrics in Naira
     */
    private function calculateRevenueMetrics($dailyEntries)
    {
        $totalSoldPieces = 0;
        foreach ($dailyEntries as $entry) {
            $soldData = FlockAnalyticsService::parseEggData($entry->daily_sold_egg);
            $totalSoldPieces += $soldData['total_pieces'];
        }
        
        $revenue = $totalSoldPieces * self::EGG_PRICE_NAIRA;
        
        return [
            'total_revenue' => $revenue,
            'revenue_per_egg' => self::EGG_PRICE_NAIRA,
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
                $maxPossible = $entry->current_birds * 1.1;
                
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
     * Calculate average daily production per bird
     */
    private function calculateProductionRate($dailyEntries, $currentBirds)
    {
        if ($currentBirds <= 0 || $dailyEntries->count() === 0) {
            return 0;
        }

        $validEntries = $dailyEntries->filter(function($entry) {
            return $entry->current_birds > 0;
        });
        
        if ($validEntries->count() === 0) {
            return 0;
        }

        $totalEggs = 0;
        $totalBirdsDays = 0;
        
        foreach ($validEntries as $entry) {
            $eggData = FlockAnalyticsService::parseEggData($entry->daily_egg_production);
            $totalEggs += $eggData['total_pieces'];
            $totalBirdsDays += $entry->current_birds;
        }
        
        if ($totalBirdsDays === 0) {
            return 0;
        }
        
        $avgBirds = $totalBirdsDays / $validEntries->count();
        $avgEggsPerDay = $totalEggs / $validEntries->count();
        
        if ($avgBirds === 0) {
            return 0;
        }
        
        $productionRate = ($avgEggsPerDay / $avgBirds) * 100;
        
        return min(100, max(0, $productionRate));
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
     * Prepare data for PDF export
     */
    private function prepareExportData($startDate, $endDate, $flockId, $data)
    {
        // Get daily entries
        $dailyEntries = DailyEntry::whereBetween('created_at', [$startDate, $endDate])
            ->with('weekEntry.flock')
            ->when($flockId, function($query, $flockId) {
                return $query->whereHas('weekEntry', function($q) use ($flockId) {
                    $q->where('flock_id', $flockId);
                });
            })
            ->get();
        
        // Calculate metrics
        $flockAnalysis = $this->analyzeFlockData($dailyEntries);
        $productionMetrics = $this->calculateProductionMetrics($dailyEntries);
        $feedMetrics = $this->calculateFeedMetrics($dailyEntries);
        $revenueMetrics = $this->calculateRevenueMetrics($dailyEntries);
        
        // Merge all metrics
        $summaryMetrics = array_merge(
            $flockAnalysis,
            $productionMetrics,
            $feedMetrics,
            $revenueMetrics
        );

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
     * Prepare poultry data for the table
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