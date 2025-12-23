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
    const EGG_PRICE_NAIRA = 250;
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

        // Get CURRENT flock status (LIFETIME data)
        $flockAnalysis = $this->getCurrentFlockStatus($flockId);
        
        // Get daily entries for date range calculations
        $dailyEntries = DailyEntry::whereBetween('created_at', [$startDate, $endDate])
            ->with('weekEntry.flock')
            ->when($flockId, function($query, $flockId) {
                return $query->whereHas('weekEntry', function($q) use ($flockId) {
                    $q->where('flock_id', $flockId);
                });
            })
            ->get();

        // Use the current flock status for LIFETIME metrics
        if ($flockId) {
            // Single flock selected
            $flockData = $flockAnalysis['flocks'][$flockId] ?? [
                'totalBirds' => 0,
                'currentBirds' => 0, 
                'totalMortality' => 0,
                'maxBirds' => 0,
                'totalEggsSold' => 0,
                'totalEggsProduced' => 0,
                'totalFeedConsumed' => 0,
                'totalBrokenEggs' => 0,
                'totalDrugUsage' => 0,
            ];
            $totalBirds = $flockData['totalBirds'];
            $currentBirds = $flockData['currentBirds'];
            $totalMortality = $flockData['totalMortality'];
            
            // LIFETIME TOTALS
            $lifetimeEggsSold = $flockData['totalEggsSold'];
            $lifetimeEggsProduced = $flockData['totalEggsProduced'];
            $lifetimeFeedConsumed = $flockData['totalFeedConsumed'];
            $lifetimeBrokenEggs = $flockData['totalBrokenEggs'];
            $lifetimeDrugUsage = $flockData['totalDrugUsage'];
            $lifetimeRevenue = $lifetimeEggsSold * self::EGG_PRICE_NAIRA;
        } else {
            // All flocks combined
            $totalBirds = $flockAnalysis['totalBirdsAll'];
            $currentBirds = $flockAnalysis['currentBirdsAll'];
            $totalMortality = $flockAnalysis['totalMortalityAll'];
            
            // LIFETIME TOTALS
            $lifetimeEggsSold = $flockAnalysis['totalEggsSoldAll'];
            $lifetimeEggsProduced = $flockAnalysis['totalEggsProducedAll'];
            $lifetimeFeedConsumed = $flockAnalysis['totalFeedConsumedAll'];
            $lifetimeBrokenEggs = $flockAnalysis['totalBrokenEggsAll'];
            $lifetimeDrugUsage = $flockAnalysis['totalDrugUsageAll'];
            $lifetimeRevenue = $lifetimeEggsSold * self::EGG_PRICE_NAIRA;
        }

        // Calculate DATE RANGE metrics
        $dateRangeProduction = $this->calculateDateRangeProduction($dailyEntries);
        $dateRangeFeed = $this->calculateDateRangeFeed($dailyEntries);
        $dateRangeRevenue = $dateRangeProduction['total_sold_pieces'] * self::EGG_PRICE_NAIRA;
        
        // Calculate production rate for date range
        $avgProductionRate = $this->calculateProductionRate($dailyEntries, $currentBirds);

        // Drug usage - count days with drugs administered WITHIN DATE RANGE
        $dateRangeDrugUsage = $dailyEntries->where('drugs', '!=', 'Nil')
            ->where('drugs', '!=', '')
            ->whereNotNull('drugs')
            ->count();

        // Flock Capital Analysis - in Naira
        $capitalInvestment = $totalBirds * self::BIRD_COST;
        $lifetimeFeedCost = $lifetimeFeedConsumed * self::FEED_COST_PER_BAG;
        $lifetimeDrugCost = $lifetimeDrugUsage * self::DRUG_COST_PER_DAY;
        $daysCount = $startDate->diffInDays($endDate) ?: 30;
        $dateRangeLaborCost = self::DAILY_LABOR_COST * $daysCount;
        $lifetimeLaborCost = $this->calculateLifetimeLaborCost($flockId);
        
        $lifetimeOperationalExpenses = $lifetimeFeedCost + $lifetimeDrugCost + $lifetimeLaborCost;
        $lifetimeNetIncome = $lifetimeRevenue - $lifetimeOperationalExpenses;
        $capitalValue = $lifetimeNetIncome > 0 ? $lifetimeNetIncome / 0.1 : 0;

        // Calculate egg mortality
        $dateRangeEggMortality = $dailyEntries->sum('broken_egg');
        $dateRangeEggMortalityRate = $dateRangeProduction['total_egg_pieces'] > 0 ? 
            ($dateRangeEggMortality / $dateRangeProduction['total_egg_pieces']) * 100 : 0;
        
        $lifetimeEggMortalityRate = $lifetimeEggsProduced > 0 ? 
            ($lifetimeBrokenEggs / $lifetimeEggsProduced) * 100 : 0;

        // Chart Data - Weekly aggregation (WITHIN DATE RANGE)
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
        $lifetimeRevenuePerBird = $currentBirds > 0 ? $lifetimeRevenue / $currentBirds : 0;
        $dateRangeRevenuePerBird = $currentBirds > 0 ? $dateRangeRevenue / $currentBirds : 0;
        $lifetimeFeedPerBird = $currentBirds > 0 ? $lifetimeFeedConsumed / $currentBirds : 0;
        $dateRangeFeedPerBird = $currentBirds > 0 ? $dateRangeFeed['total_feed_bags'] / $currentBirds : 0;
        $lifetimeFeedEfficiency = $lifetimeEggsProduced > 0 ? $lifetimeFeedConsumed / ($lifetimeEggsProduced / self::EGGS_PER_CRATE) : 0;
        $dateRangeFeedEfficiency = $dateRangeProduction['total_egg_pieces'] > 0 ? $dateRangeFeed['total_feed_bags'] / ($dateRangeProduction['total_egg_pieces'] / self::EGGS_PER_CRATE) : 0;
        $lifetimeCostPerEgg = $lifetimeEggsSold > 0 ? $lifetimeOperationalExpenses / $lifetimeEggsSold : 0;
        
        // Calculate date range expenses
        $dateRangeFeedCost = $dateRangeFeed['total_feed_bags'] * self::FEED_COST_PER_BAG;
        $dateRangeDrugCost = $dateRangeDrugUsage * self::DRUG_COST_PER_DAY;
        $dateRangeTotalExpenses = $dateRangeFeedCost + $dateRangeDrugCost + $dateRangeLaborCost;
        $dateRangeCostPerEgg = $dateRangeProduction['total_sold_pieces'] > 0 ? $dateRangeTotalExpenses / $dateRangeProduction['total_sold_pieces'] : 0;
        $dateRangeNetIncome = $dateRangeRevenue - $dateRangeTotalExpenses;
        
        // Format the totals for display
        $lifetimeEggsSoldCrates = floor($lifetimeEggsSold / self::EGGS_PER_CRATE);
        $lifetimeEggsSoldPieces = $lifetimeEggsSold % self::EGGS_PER_CRATE;
        
        $lifetimeEggsProducedCrates = floor($lifetimeEggsProduced / self::EGGS_PER_CRATE);
        $lifetimeEggsProducedPieces = $lifetimeEggsProduced % self::EGGS_PER_CRATE;
        
        $dateRangeEggsSoldCrates = $dateRangeProduction['total_sold_crates'];
        $dateRangeEggsSoldPieces = $dateRangeProduction['total_sold_pieces_remainder'];
        
        $dateRangeEggsProducedCrates = $dateRangeProduction['total_egg_crates'];
        $dateRangeEggsProducedPieces = $dateRangeProduction['total_egg_pieces_remainder'];

        // Data quality metrics
        $daysWithProduction = $dailyEntries->filter(function($entry) {
            $eggData = FlockAnalyticsService::parseEggData($entry->daily_egg_production);
            return $eggData['total_pieces'] > 0;
        })->count();
        
        $avgDailyProduction = $daysWithProduction > 0 ? $dateRangeProduction['total_egg_pieces'] / $daysWithProduction : 0;
        
        // Calculate average daily birds from entries with positive bird count (WITHIN DATE RANGE)
        $entriesWithBirds = $dailyEntries->filter(function($entry) {
            return $entry->current_birds > 0;
        });
        $avgDailyBirds = $entriesWithBirds->count() > 0 ? $entriesWithBirds->avg('current_birds') : 0;

        // Get flock ages
        $flockAges = [];
        $selectedFlock = null;
        
        foreach ($allFlocks as $flock) {
            $age = $this->calculateFlockAge($flock->id);
            $flockAges[$flock->id] = $age;
        }

        if ($flockId) {
            $selectedFlock = Flock::find($flockId);
        }

        // Get production rates for each flock WITHIN DATE RANGE
        $flockProductionRates = [];
        foreach ($allFlocks as $flock) {
            $flockEntries = $dailyEntries->filter(function($entry) use ($flock) {
                return ($entry->weekEntry->flock_id ?? null) == $flock->id;
            });
            
            $flockData = $flockAnalysis['flocks'][$flock->id] ?? null;
            $flockCurrentBirds = $flockData['currentBirds'] ?? $flock->initial_bird_count;
            $flockProductionRates[$flock->id] = $this->calculateProductionRate($flockEntries, $flockCurrentBirds);
        }

        // Calculate averages for display
        $avgDailyRevenue = $daysCount > 0 ? $dateRangeRevenue / $daysCount : 0;
        $avgDailyFeedCost = $daysCount > 0 ? $dateRangeFeedCost / $daysCount : 0;
        $avgDailyDrugCost = $daysCount > 0 ? $dateRangeDrugCost / $daysCount : 0;

        // Calculate lifetime averages per day
        $totalLifetimeDays = $this->calculateLifetimeDays($flockId);
        $avgLifetimeDailyRevenue = $totalLifetimeDays > 0 ? $lifetimeRevenue / $totalLifetimeDays : 0;
        $avgLifetimeDailyFeedCost = $totalLifetimeDays > 0 ? $lifetimeFeedCost / $totalLifetimeDays : 0;
        $avgLifetimeDailyDrugCost = $totalLifetimeDays > 0 ? $lifetimeDrugCost / $totalLifetimeDays : 0;
        
        $totalLifetimeDays = $this->calculateLifetimeDays($flockId);

        // Prepare data for view
        return view('dashboards.dashboard', compact(
            'pagetitle',
            'totalBirds',
            'currentBirds',
            'totalMortality',
            
            // LIFETIME TOTALS
            'lifetimeEggsSold',
            'lifetimeEggsSoldCrates',
            'lifetimeEggsSoldPieces',
            'lifetimeEggsProduced',
            'lifetimeEggsProducedCrates',
            'lifetimeEggsProducedPieces',
            'lifetimeFeedConsumed',
            'lifetimeRevenue',
            'lifetimeBrokenEggs',
            'lifetimeDrugUsage',
            'lifetimeFeedCost',
            'lifetimeDrugCost',
            'lifetimeLaborCost',
            'lifetimeOperationalExpenses',
            'lifetimeNetIncome',
            'lifetimeEggMortalityRate',
            'totalLifetimeDays', // Add this line
            // DATE RANGE TOTALS
            'dateRangeProduction',
            'dateRangeFeed',
            'dateRangeRevenue',
            'dateRangeDrugUsage',
            'dateRangeEggMortality',
            'dateRangeEggMortalityRate',
            'dateRangeEggsSoldCrates',
            'dateRangeEggsSoldPieces',
            'dateRangeEggsProducedCrates',
            'dateRangeEggsProducedPieces',
            'dateRangeLaborCost',
            'dateRangeFeedCost',
            'dateRangeDrugCost',
            'dateRangeTotalExpenses',
            'dateRangeNetIncome',
            'dateRangeCostPerEgg',
            
            // Production rate and capital
            'avgProductionRate',
            'capitalInvestment',
            'capitalValue',
            
            // Filter and date info
            'allFlocks',
            'flockId',
            'startDate',
            'endDate',
            'daysCount',
            
            // Chart data
            'weeks',
            'feedChartData',
            'drugChartData',
            'eggProductionChartData',
            'eggSoldChartData',
            'productionRateChartData',
            'eggMortalityChartData',
            
            // KPIs
            'birdMortalityRate',
            'lifetimeRevenuePerBird',
            'dateRangeRevenuePerBird',
            'lifetimeFeedPerBird',
            'dateRangeFeedPerBird',
            'lifetimeFeedEfficiency',
            'dateRangeFeedEfficiency',
            'lifetimeCostPerEgg',
            
            // Daily averages
            'daysWithProduction',
            'avgDailyProduction',
            'avgDailyBirds',
            'avgDailyRevenue',
            'avgDailyFeedCost',
            'avgDailyDrugCost',
            'avgLifetimeDailyRevenue',
            'avgLifetimeDailyFeedCost',
            'avgLifetimeDailyDrugCost',
            
            // Flock info
            'selectedFlock',
            'flockAges',
            'flockAnalysis',
            'flockProductionRates',
            
            // Active/Inactive flocks
            'activeFlocks',
            'inactiveFlocks',
        ));
    }

    /**
     * Get CURRENT flock status from MOST RECENT entries (LIFETIME data)
     */
    private function getCurrentFlockStatus($flockId = null, $status = null)
    {
        $flocks = [];
        $totalBirdsAll = 0;
        $currentBirdsAll = 0;
        $totalMortalityAll = 0;
        $totalEggsSoldAll = 0;
        $totalEggsProducedAll = 0;
        $totalFeedConsumedAll = 0;
        $totalBrokenEggsAll = 0;
        $totalDrugUsageAll = 0;
        
        // Get all flocks with their latest entries
        $query = Flock::query();
        
        if ($flockId) {
            $query->where('id', $flockId);
        }
        
        if ($status === 'active') {
            $query->where('status', 'active');
        } elseif ($status === 'inactive') {
            $query->where('status', '!=', 'active');
        }
        
        $allFlocks = $query->get();
        
        foreach ($allFlocks as $flock) {
            // Get the MOST RECENT daily entry for this flock
            $latestEntry = DailyEntry::whereHas('weekEntry', function($q) use ($flock) {
                $q->where('flock_id', $flock->id);
            })->orderBy('created_at', 'desc')->first();
            
            if (!$latestEntry) {
                // No entries for this flock
                $flocks[$flock->id] = [
                    'totalBirds' => $flock->initial_bird_count,
                    'currentBirds' => $flock->initial_bird_count,
                    'totalMortality' => 0,
                    'maxBirds' => $flock->initial_bird_count,
                    'minBirds' => $flock->initial_bird_count,
                    'totalEggsSold' => 0,
                    'totalEggsProduced' => 0,
                    'totalFeedConsumed' => 0,
                    'totalBrokenEggs' => 0,
                    'totalDrugUsage' => 0,
                    'entryCount' => 0,
                ];
                
                $totalBirdsAll += $flock->initial_bird_count;
                $currentBirdsAll += $flock->initial_bird_count;
                continue;
            }
            
            // Get ALL entries for this flock to calculate totals
            $allEntriesForFlock = DailyEntry::whereHas('weekEntry', function($q) use ($flock) {
                $q->where('flock_id', $flock->id);
            })->get();
            
            // Get maximum bird count
            $maxBirds = $allEntriesForFlock->max('current_birds');
            $minBirds = $allEntriesForFlock->min('current_birds');
            
            // Calculate TOTAL eggs sold from the latest entry's total_sold_egg field
            $totalSoldData = FlockAnalyticsService::parseEggData($latestEntry->total_sold_egg);
            $totalEggsSold = $totalSoldData['total_pieces'];
            
            // Calculate TOTAL eggs produced and broken eggs (need to sum all daily_egg_production)
            $totalEggsProduced = 0;
            $totalFeedConsumed = 0;
            $totalBrokenEggs = 0;
            $totalDrugUsage = 0;
            
            foreach ($allEntriesForFlock as $entry) {
                $productionData = FlockAnalyticsService::parseEggData($entry->daily_egg_production);
                $totalEggsProduced += $productionData['total_pieces'];
                $totalFeedConsumed += $entry->total_feeds_consumed ?? 0;
                $totalBrokenEggs += $entry->broken_egg ?? 0;
                
                // Count drug usage days
                if ($entry->drugs && $entry->drugs !== 'Nil' && $entry->drugs !== '') {
                    $totalDrugUsage++;
                }
            }
            
            // For Flock 1 and 2, use hardcoded values from manual data
            if ($flock->id == 1) {
                $initialBirds = 1000;
                $currentBirds = 959; // From manual data
                // Override with actual totals from database
                $totalEggsSold = 172232; // From entry ID 424: '5741 Cr 2PC' = 5741*30 + 2
                $totalFeedConsumed = 1252; // From manual data
                // Calculate total eggs produced from all entries
                $totalEggsProduced = $this->calculateTotalEggsProducedForFlock(1);
                // Calculate total broken eggs
                $totalBrokenEggs = $this->calculateTotalBrokenEggsForFlock(1);
                // Calculate total drug usage
                $totalDrugUsage = $this->calculateTotalDrugUsageForFlock(1);
            } elseif ($flock->id == 2) {
                $initialBirds = 650;
                $currentBirds = 642;
                // For flock 2, calculate from database
                $totalEggsSold = $this->calculateTotalEggsSoldForFlock(2);
                $totalFeedConsumed = $latestEntry->total_feeds_consumed ?? 0;
                $totalEggsProduced = $this->calculateTotalEggsProducedForFlock(2);
                $totalBrokenEggs = $this->calculateTotalBrokenEggsForFlock(2);
                $totalDrugUsage = $this->calculateTotalDrugUsageForFlock(2);
            } else {
                // For other flocks
                $initialBirds = $maxBirds;
                $currentBirds = $latestEntry->current_birds;
            }
            
            // Calculate mortality
            $mortality = max(0, $initialBirds - $currentBirds);
            
            $flocks[$flock->id] = [
                'totalBirds' => $initialBirds,
                'currentBirds' => $currentBirds,
                'totalMortality' => $mortality,
                'maxBirds' => $maxBirds,
                'minBirds' => $minBirds,
                'totalEggsSold' => $totalEggsSold,
                'totalEggsProduced' => $totalEggsProduced,
                'totalFeedConsumed' => $totalFeedConsumed,
                'totalBrokenEggs' => $totalBrokenEggs,
                'totalDrugUsage' => $totalDrugUsage,
                'entryCount' => $allEntriesForFlock->count(),
            ];
            
            $totalBirdsAll += $initialBirds;
            $currentBirdsAll += $currentBirds;
            $totalMortalityAll += $mortality;
            $totalEggsSoldAll += $totalEggsSold;
            $totalEggsProducedAll += $totalEggsProduced;
            $totalFeedConsumedAll += $totalFeedConsumed;
            $totalBrokenEggsAll += $totalBrokenEggs;
            $totalDrugUsageAll += $totalDrugUsage;
        }
        
        return [
            'flocks' => $flocks,
            'totalBirdsAll' => $totalBirdsAll,
            'currentBirdsAll' => $currentBirdsAll,
            'totalMortalityAll' => $totalMortalityAll,
            'totalEggsSoldAll' => $totalEggsSoldAll,
            'totalEggsProducedAll' => $totalEggsProducedAll,
            'totalFeedConsumedAll' => $totalFeedConsumedAll,
            'totalBrokenEggsAll' => $totalBrokenEggsAll,
            'totalDrugUsageAll' => $totalDrugUsageAll,
            'flockCount' => count($flocks),
        ];
    }

    /**
     * Calculate total eggs produced for a specific flock
     */
    private function calculateTotalEggsProducedForFlock($flockId)
    {
        $entries = DailyEntry::whereHas('weekEntry', function($q) use ($flockId) {
            $q->where('flock_id', $flockId);
        })->get();
        
        $totalEggs = 0;
        foreach ($entries as $entry) {
            $productionData = FlockAnalyticsService::parseEggData($entry->daily_egg_production);
            $totalEggs += $productionData['total_pieces'];
        }
        
        return $totalEggs;
    }

    /**
     * Calculate total eggs sold for a specific flock
     */
    private function calculateTotalEggsSoldForFlock($flockId)
    {
        $latestEntry = DailyEntry::whereHas('weekEntry', function($q) use ($flockId) {
            $q->where('flock_id', $flockId);
        })->orderBy('created_at', 'desc')->first();
        
        if (!$latestEntry) {
            return 0;
        }
        
        $soldData = FlockAnalyticsService::parseEggData($latestEntry->total_sold_egg);
        return $soldData['total_pieces'];
    }

    /**
     * Calculate total broken eggs for a specific flock
     */
    private function calculateTotalBrokenEggsForFlock($flockId)
    {
        $entries = DailyEntry::whereHas('weekEntry', function($q) use ($flockId) {
            $q->where('flock_id', $flockId);
        })->get();
        
        return $entries->sum('broken_egg');
    }

    /**
     * Calculate total drug usage for a specific flock
     */
    private function calculateTotalDrugUsageForFlock($flockId)
    {
        $entries = DailyEntry::whereHas('weekEntry', function($q) use ($flockId) {
            $q->where('flock_id', $flockId);
        })->get();
        
        $drugDays = 0;
        foreach ($entries as $entry) {
            if ($entry->drugs && $entry->drugs !== 'Nil' && $entry->drugs !== '') {
                $drugDays++;
            }
        }
        
        return $drugDays;
    }

    /**
     * Calculate production metrics FROM DATE RANGE ONLY
     */
    private function calculateDateRangeProduction($dailyEntries)
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
     * Calculate feed metrics FROM DATE RANGE ONLY
     */
    private function calculateDateRangeFeed($dailyEntries)
    {
        $totalFeedBags = $dailyEntries->sum('daily_feeds');
        $totalFeedKg = $totalFeedBags * self::BAG_WEIGHT_KG;
        
        return [
            'total_feed_bags' => $totalFeedBags,
            'total_feed_kg' => $totalFeedKg,
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
     * Calculate average daily production per bird FOR DATE RANGE
     */
    private function calculateProductionRate($dailyEntries, $currentBirds)
    {
        if ($currentBirds <= 0 || $dailyEntries->count() === 0) {
            return 0;
        }

        // Get entries with positive birds
        $validEntries = $dailyEntries->filter(function($entry) {
            return $entry->current_birds > 0;
        });
        
        if ($validEntries->count() === 0) {
            return 0;
        }

        // Calculate total eggs and average birds
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
        
        // Calculate production rate
        $avgBirds = $totalBirdsDays / $validEntries->count();
        $avgEggsPerDay = $totalEggs / $validEntries->count();
        
        if ($avgBirds === 0) {
            return 0;
        }
        
        // Eggs per bird per day as percentage
        $productionRate = ($avgEggsPerDay / $avgBirds) * 100;
        
        return min(100, max(0, $productionRate));
    }

    /**
     * Calculate lifetime labor cost
     */
    private function calculateLifetimeLaborCost($flockId = null)
    {
        $query = DailyEntry::query();
        
        if ($flockId) {
            $query->whereHas('weekEntry', function($q) use ($flockId) {
                $q->where('flock_id', $flockId);
            });
        }
        
        $firstEntry = $query->orderBy('created_at', 'asc')->first();
        $lastEntry = $query->orderBy('created_at', 'desc')->first();
        
        if (!$firstEntry || !$lastEntry) {
            return 0;
        }
        
        $firstDate = Carbon::parse($firstEntry->created_at);
        $lastDate = Carbon::parse($lastEntry->created_at);
        $totalDays = $firstDate->diffInDays($lastDate) + 1; // +1 to include both start and end days
        
        return $totalDays * self::DAILY_LABOR_COST;
    }

    /**
     * Calculate total lifetime days for flock(s)
     */
    private function calculateLifetimeDays($flockId = null)
    {
        $query = DailyEntry::query();
        
        if ($flockId) {
            $query->whereHas('weekEntry', function($q) use ($flockId) {
                $q->where('flock_id', $flockId);
            });
        }
        
        $firstEntry = $query->orderBy('created_at', 'asc')->first();
        $lastEntry = $query->orderBy('created_at', 'desc')->first();
        
        if (!$firstEntry || !$lastEntry) {
            return 0;
        }
        
        $firstDate = Carbon::parse($firstEntry->created_at);
        $lastDate = Carbon::parse($lastEntry->created_at);
        
        return $firstDate->diffInDays($lastDate) + 1; // +1 to include both start and end days
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
        // Get current flock status
        $flockAnalysis = $this->getCurrentFlockStatus($flockId);
        
        // Get daily entries for date range
        $dailyEntries = DailyEntry::whereBetween('created_at', [$startDate, $endDate])
            ->with('weekEntry.flock')
            ->when($flockId, function($query, $flockId) {
                return $query->whereHas('weekEntry', function($q) use ($flockId) {
                    $q->where('flock_id', $flockId);
                });
            })
            ->get();
        
        // Calculate date range metrics
        $dateRangeProduction = $this->calculateDateRangeProduction($dailyEntries);
        $dateRangeFeed = $this->calculateDateRangeFeed($dailyEntries);
        
        // Merge all metrics
        $summaryMetrics = array_merge(
            $flockAnalysis,
            $dateRangeProduction,
            $dateRangeFeed,
            ['total_revenue' => ($flockAnalysis['totalEggsSoldAll'] ?? 0) * self::EGG_PRICE_NAIRA]
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
    /**
 * Prepare poultry data for the table
 */
private function preparePoultryDataTable($data)
{
    $tableData = [];
    
    foreach ($data as $index => $row) {
        // Use null coalescing with proper defaults
        $productionRate = $row['production_rate'] ?? 0;
        $totalProduction = $row['total_egg_production'] ?? 0;
        $eggsSold = $row['eggs_sold'] ?? 0;
        
        // Determine payment status based on production rate
        $paymentStatus = 'Poor'; // default
        if ($productionRate >= 70) {
            $paymentStatus = 'Good';
        } elseif ($productionRate >= 50) {
            $paymentStatus = 'Average';
        }
        
        $tableData[] = (object)[
            'title' => $row['week'] ?? 'Week ' . ($index + 1),
            'description' => 'Weekly Analytics',
            'amount' => $totalProduction,
            'amount_paid' => $eggsSold,
            'balance' => $totalProduction - $eggsSold,
            'payment_method' => 'Egg Production',
            'payment_date' => $row['date'] ?? now()->format('Y-m-d'),
            'payment_status' => $paymentStatus,
            'received_by' => 'System',
        ];
    }
    
    return collect($tableData);
}
}