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
        $allFlocks = Flock::all();
        
        // Separate active and inactive flocks
        $activeFlocks = Flock::where('status', 'active')->get();
        $inactiveFlocks = Flock::where('status', '!=', 'active')->get();

        // Get all daily entries for calculations
        $dailyEntriesQuery = DailyEntry::whereBetween('created_at', [$startDate, $endDate])
            ->with('weekEntry.flock')
            ->when($flockId, function($query, $flockId) {
                return $query->whereHas('weekEntry', function($q) use ($flockId) {
                    $q->where('flock_id', $flockId);
                });
            });

        $dailyEntries = $dailyEntriesQuery->get();
        $totalEntriesCount = $dailyEntries->count();

        // Analyze flock data using ALL historical data for accurate counts
        $flockAnalysis = $this->analyzeFlockData($dailyEntries);

        // Use accurate data from analysis
        if ($flockId) {
            // Single flock selected
            $flockData = $flockAnalysis['flocks'][$flockId] ?? null;
            if ($flockData) {
                $totalBirds = $flockData['totalBirds'] ?? 0;
                $currentBirds = $flockData['currentBirds'] ?? 0;
                $totalMortality = $flockData['totalMortality'] ?? 0;
            } else {
                // Fallback to flock model data
                $flock = Flock::find($flockId);
                $totalBirds = $flock->initial_bird_count ?? 0;
                $currentBirds = $flock->initial_bird_count ?? 0;
                $totalMortality = 0;
            }
        } else {
            // All flocks combined
            $totalBirds = $flockAnalysis['totalBirdsAll'] ?? 0;
            $currentBirds = $flockAnalysis['currentBirdsAll'] ?? 0;
            $totalMortality = $flockAnalysis['totalMortalityAll'] ?? 0;
        }

        // Calculate production metrics from daily entries
        $productionMetrics = $this->calculateProductionMetrics($dailyEntries);
        $feedMetrics = $this->calculateFeedMetrics($dailyEntries);
        $revenueMetrics = $this->calculateRevenueMetrics($dailyEntries);

        // Calculate production rate
        $avgProductionRate = $this->calculateProductionRate($dailyEntries, $currentBirds);

        // Drug usage
        $totalDrugUsage = $dailyEntries->where('drugs', '!=', 'Nil')
            ->where('drugs', '!=', '')
            ->whereNotNull('drugs')
            ->count();

        // Financial calculations
        $capitalInvestment = $totalBirds * self::BIRD_COST;
        $feedCost = $feedMetrics['total_feed_bags'] * self::FEED_COST_PER_BAG;
        $drugCost = $totalDrugUsage * self::DRUG_COST_PER_DAY;
        $daysCount = $startDate->diffInDays($endDate) ?: 30;
        $laborCost = self::DAILY_LABOR_COST * $daysCount;
        $operationalExpenses = $feedCost + $drugCost + $laborCost;
        $netIncome = $revenueMetrics['total_revenue'] - $operationalExpenses;
        $capitalValue = $netIncome > 0 ? $netIncome / 0.1 : 0;

        // Calculate KPIs
        $birdMortalityRate = $totalBirds > 0 ? ($totalMortality / $totalBirds) * 100 : 0;
        $revenuePerBird = $currentBirds > 0 ? $revenueMetrics['total_revenue'] / $currentBirds : 0;
        $feedPerBird = $currentBirds > 0 ? $feedMetrics['total_feed_bags'] / $currentBirds : 0;
        $feedEfficiency = $productionMetrics['total_egg_pieces'] > 0 ? 
            $feedMetrics['total_feed_bags'] / ($productionMetrics['total_egg_pieces'] / self::EGGS_PER_CRATE) : 0;
        $costPerEgg = $productionMetrics['total_sold_pieces'] > 0 ? 
            $operationalExpenses / $productionMetrics['total_sold_pieces'] : 0;
        $eggMortalityRate = $productionMetrics['total_egg_pieces'] > 0 ? 
            ($productionMetrics['total_broken_eggs'] / $productionMetrics['total_egg_pieces']) * 100 : 0;
        $eggSalesEfficiency = ($productionMetrics['total_sold_pieces'] + $productionMetrics['total_broken_eggs']) > 0 ? 
            ($productionMetrics['total_sold_pieces'] / ($productionMetrics['total_sold_pieces'] + $productionMetrics['total_broken_eggs'])) * 100 : 0;
        $eggDisposalRate = $productionMetrics['total_egg_pieces'] > 0 ? 
            (($productionMetrics['total_sold_pieces'] + $productionMetrics['total_broken_eggs']) / $productionMetrics['total_egg_pieces']) * 100 : 0;

        // Data quality metrics
        $unrealisticEntries = $this->validateEggProduction($dailyEntries);
        $hasDataQualityIssues = count($unrealisticEntries) > 0;
        
        $daysWithProduction = $dailyEntries->filter(function($entry) {
            $eggData = FlockAnalyticsService::parseEggData($entry->daily_egg_production);
            return $eggData['total_pieces'] > 0;
        })->count();
        
        $avgDailyProduction = $daysWithProduction > 0 ? $productionMetrics['total_egg_pieces'] / $daysWithProduction : 0;
        
        $entriesWithBirds = $dailyEntries->filter(function($entry) {
            return $entry->current_birds > 0;
        });
        $avgDailyBirds = $entriesWithBirds->count() > 0 ? $entriesWithBirds->avg('current_birds') : 0;

        // Get flock production rates for each flock
        $flockProductionRates = [];
        foreach ($allFlocks as $flock) {
            $flockEntries = $dailyEntries->filter(function($entry) use ($flock) {
                return ($entry->weekEntry->flock_id ?? null) == $flock->id;
            });
            
            $flockData = $flockAnalysis['flocks'][$flock->id] ?? null;
            $flockCurrentBirds = $flockData['currentBirds'] ?? $flock->initial_bird_count;
            $flockProductionRates[$flock->id] = $this->calculateProductionRate($flockEntries, $flockCurrentBirds);
        }

        // Chart Data - Weekly aggregation
        $chartData = $dailyEntries->groupBy(function($entry) {
            return $entry->created_at->format('Y-W');
        })->map(function($weekEntries) {
            $weekProduction = 0;
            $weekSales = 0;
            $weekBroken = 0;
            $weekBirds = 0;
            $weekFeed = 0;
            $weekDrugs = 0;
            $entryCount = $weekEntries->count();
            
            foreach ($weekEntries as $entry) {
                $productionData = FlockAnalyticsService::parseEggData($entry->daily_egg_production);
                $salesData = FlockAnalyticsService::parseEggData($entry->daily_sold_egg);
                $weekProduction += $productionData['total_pieces'];
                $weekSales += $salesData['total_pieces'];
                $weekBroken += $entry->broken_egg ?? 0;
                $weekBirds += $entry->current_birds;
                $weekFeed += $entry->daily_feeds;
                if ($entry->drugs && $entry->drugs != 'Nil' && $entry->drugs != '') {
                    $weekDrugs++;
                }
            }
            
            $avgWeekBirds = $entryCount > 0 ? $weekBirds / $entryCount : 0;

            $weekProductionRate = 0;
            if ($avgWeekBirds > 0 && $weekProduction > 0) {
                $avgEggsPerBirdPerDay = ($weekProduction / $entryCount) / $avgWeekBirds;
                $weekProductionRate = min(100, max(0, $avgEggsPerBirdPerDay * 100));
            }

            return [
                'feed_bags' => $weekFeed,
                'drugs' => $weekDrugs,
                'eggs_produced' => $weekProduction,
                'eggs_sold' => $weekSales,
                'eggs_broken' => $weekBroken,
                'production_rate' => $weekProductionRate,
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
                'avg_birds' => 0
            ];
            
            $feedChartData[] = $data['feed_bags'];
            $drugChartData[] = $data['drugs'];
            $eggProductionChartData[] = $data['eggs_produced'];
            $eggSoldChartData[] = $data['eggs_sold'];
            $productionRateChartData[] = $data['production_rate'];
            $eggMortalityChartData[] = $data['eggs_broken'];
        }

        // Get flock ages and prepare active/inactive analysis
        $flockAges = [];
        $selectedFlock = null;
        
        $activeFlockAnalysis = ['flocks' => [], 'totalBirdsAll' => 0, 'currentBirdsAll' => 0, 'totalMortalityAll' => 0, 'flockCount' => 0];
        $inactiveFlockAnalysis = ['flocks' => [], 'totalBirdsAll' => 0, 'currentBirdsAll' => 0, 'totalMortalityAll' => 0, 'flockCount' => 0];
        
        // Calculate production metrics for active and inactive flocks
        $activeEntries = $dailyEntries->filter(function($entry) {
            $flock = $entry->weekEntry->flock ?? null;
            return $flock && $flock->status === 'active';
        });
        
        $inactiveEntries = $dailyEntries->filter(function($entry) {
            $flock = $entry->weekEntry->flock ?? null;
            return $flock && $flock->status !== 'active';
        });
        
        $activeProductionMetrics = $this->calculateProductionMetrics($activeEntries);
        $inactiveProductionMetrics = $this->calculateProductionMetrics($inactiveEntries);
        
        foreach ($allFlocks as $flock) {
            $age = $this->calculateFlockAge($flock->id);
            $flockAges[$flock->id] = $age;
            
            // Get flock data from analysis
            $flockData = $flockAnalysis['flocks'][$flock->id] ?? null;
            
            if ($flockData) {
                if ($flock->status === 'active') {
                    $activeFlockAnalysis['flocks'][$flock->id] = $flockData;
                    $activeFlockAnalysis['totalBirdsAll'] += $flockData['totalBirds'];
                    $activeFlockAnalysis['currentBirdsAll'] += $flockData['currentBirds'];
                    $activeFlockAnalysis['totalMortalityAll'] += $flockData['totalMortality'];
                    $activeFlockAnalysis['flockCount']++;
                } else {
                    $inactiveFlockAnalysis['flocks'][$flock->id] = $flockData;
                    $inactiveFlockAnalysis['totalBirdsAll'] += $flockData['totalBirds'];
                    $inactiveFlockAnalysis['currentBirdsAll'] += $flockData['currentBirds'];
                    $inactiveFlockAnalysis['totalMortalityAll'] += $flockData['totalMortality'];
                    $inactiveFlockAnalysis['flockCount']++;
                }
            }
        }

        if ($flockId) {
            $selectedFlock = Flock::find($flockId);
        }

        // Prepare individual variables for view
        $totalEggProductionCrates = $productionMetrics['total_egg_crates'];
        $totalEggProductionPieces = $productionMetrics['total_egg_pieces_remainder'];
        $totalEggProductionTotalPieces = $productionMetrics['total_egg_pieces'];

        $totalFeedBags = $feedMetrics['total_feed_bags'];
        $totalFeedKg = $feedMetrics['total_feed_kg'];

        $totalEggsSoldCrates = $productionMetrics['total_sold_crates'];
        $totalEggsSoldPieces = $productionMetrics['total_sold_pieces_remainder'];
        $totalEggsSoldTotalPieces = $productionMetrics['total_sold_pieces'];

        $totalRevenue = $revenueMetrics['total_revenue'];
        $totalEggMortality = $productionMetrics['total_broken_eggs'];

        // Calculate averages for display
        $avgDailyRevenue = $daysCount > 0 ? $totalRevenue / $daysCount : 0;
        $avgDailyFeedCost = $daysCount > 0 ? $feedCost / $daysCount : 0;
        $avgDailyDrugCost = $daysCount > 0 ? $drugCost / $daysCount : 0;

        return view('dashboards.dashboard', compact(
            'pagetitle',
            'totalBirds',
            'currentBirds',
            'totalMortality',
            
            'productionMetrics',
            'feedMetrics', 
            'revenueMetrics',
            
            'totalEggProductionCrates',
            'totalEggProductionPieces', 
            'totalEggProductionTotalPieces',
            'totalFeedBags',
            'totalFeedKg',
            'totalEggsSoldCrates',
            'totalEggsSoldPieces',
            'totalEggsSoldTotalPieces',
            'totalRevenue',
            'totalEggMortality',
            
            'totalDrugUsage',
            'avgProductionRate',
            'eggMortalityRate',
            
            'capitalInvestment',
            'operationalExpenses',
            'netIncome',
            'capitalValue',
            
            'allFlocks',
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
            'eggSalesEfficiency',
            'eggDisposalRate',
            
            'hasDataQualityIssues',
            'unrealisticEntries',
            'daysWithProduction',
            'avgDailyProduction',
            'avgDailyBirds',
            'avgDailyRevenue',
            'avgDailyFeedCost',
            'avgDailyDrugCost',
            
            'selectedFlock',
            'flockAges',
            'flockAnalysis',
            'flockProductionRates',
            
            'activeFlocks',
            'inactiveFlocks',
            'activeFlockAnalysis',
            'inactiveFlockAnalysis',
            'activeProductionMetrics',
            'inactiveProductionMetrics',
            'totalEntriesCount'
        ));
    }

    /**
     * Analyze flock data from ALL historical data for accurate counts
     */
    private function analyzeFlockData($dailyEntries)
    {
        $flocks = [];
        $totalBirdsAll = 0;
        $currentBirdsAll = 0;
        $totalMortalityAll = 0;
        
        // Get unique flock IDs from entries
        $flockIds = $dailyEntries->pluck('weekEntry.flock_id')->unique()->filter()->toArray();
        
        // If no flock IDs found, try to get from flock table
        if (empty($flockIds)) {
            $flockIds = Flock::pluck('id')->toArray();
        }
        
        foreach ($flockIds as $flockId) {
            if ($flockId == 0) continue;
            
            // Get ALL entries for this flock (not filtered by date) for accurate initial count
            $allEntriesForFlock = DailyEntry::whereHas('weekEntry', function($q) use ($flockId) {
                $q->where('flock_id', $flockId);
            })->orderBy('created_at')->get();
            
            $flock = Flock::find($flockId);
            if (!$flock) continue;
            
            // Use flock's initial bird count as total birds
            $initialBirds = $flock->initial_bird_count;
            
            // Get current birds from latest entry in the filtered period
            $latestEntry = $dailyEntries->where('weekEntry.flock_id', $flockId)->sortByDesc('created_at')->first();
            $currentBirds = $latestEntry ? $latestEntry->current_birds : $initialBirds;
            
            // Calculate mortality
            $mortality = max(0, $initialBirds - $currentBirds);
            
            // Get max birds from all entries
            $maxBirds = $allEntriesForFlock->isNotEmpty() ? $allEntriesForFlock->max('current_birds') : $initialBirds;
            $minBirds = $allEntriesForFlock->isNotEmpty() ? $allEntriesForFlock->min('current_birds') : $initialBirds;
            
            // Get date range
            $firstDate = $allEntriesForFlock->isNotEmpty() ? $allEntriesForFlock->first()->created_at : null;
            $lastDate = $allEntriesForFlock->isNotEmpty() ? $allEntriesForFlock->last()->created_at : null;
            
            $flocks[$flockId] = [
                'totalBirds' => $initialBirds,
                'currentBirds' => $currentBirds,
                'totalMortality' => $mortality,
                'maxBirds' => $maxBirds,
                'minBirds' => $minBirds,
                'entryCount' => $dailyEntries->where('weekEntry.flock_id', $flockId)->count(),
                'totalEntryCount' => $allEntriesForFlock->count(),
                'firstDate' => $firstDate ? $firstDate->format('Y-m-d') : null,
                'lastDate' => $lastDate ? $lastDate->format('Y-m-d') : null,
                'trueInitialBirds' => $initialBirds,
                'trueCurrentBirds' => $currentBirds,
                'flock' => $flock
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
                $maxPossible = $entry->current_birds * 1.1; // Allow 10% margin
                
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

        if ($format === 'pdf') {
            $dailyEntries = DailyEntry::whereBetween('created_at', [$startDate, $endDate])
                ->with('weekEntry.flock')
                ->when($flockId, function($query, $flockId) {
                    return $query->whereHas('weekEntry', function($q) use ($flockId) {
                        $q->where('flock_id', $flockId);
                    });
                })
                ->get();
            
            $flockAnalysis = $this->analyzeFlockData($dailyEntries);
            $productionMetrics = $this->calculateProductionMetrics($dailyEntries);
            
            $exportData = [
                'flockAnalysis' => $flockAnalysis,
                'productionMetrics' => $productionMetrics,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'flockId' => $flockId,
                'selectedFlock' => $flockId ? Flock::find($flockId) : null,
            ];
            
            $pdf = Pdf::loadView('exports.poultry_analytics_pdf', $exportData)
                ->setPaper('a4', 'landscape');
            
            $filename = 'poultry_analytics_' . now()->format('Ymd_His') . '.pdf';
            return $pdf->download($filename);
        }

        return Excel::download(
            new PoultryAnalyticsExport($startDate, $endDate, $flockId),
            'poultry_analytics_' . now()->format('Ymd_His') . '.csv'
        );
    }
}