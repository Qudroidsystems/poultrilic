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

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:dashboard', ['only' => ['index', 'export']]);
    }

    // Constants
    const EGGS_PER_CRATE = 30;
    const BAG_WEIGHT_KG = 50; // For information only - actual calculation treats each unit as 1 bag

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
     * Calculate total sold eggs by summing daily_sold_egg field
     */
    private function getTotalSoldEggs($dailyEntries)
    {
        if ($dailyEntries->count() === 0) {
            return ['crates' => 0, 'pieces' => 0, 'total_pieces' => 0];
        }

        $totalSoldPieces = 0;
        foreach ($dailyEntries as $entry) {
            $soldData = $this->parseEggData($entry->daily_sold_egg);
            $totalSoldPieces += $soldData['total_pieces'];
        }

        $crates = floor($totalSoldPieces / self::EGGS_PER_CRATE);
        $pieces = $totalSoldPieces % self::EGGS_PER_CRATE;

        return [
            'crates' => $crates,
            'pieces' => $pieces,
            'total_pieces' => $totalSoldPieces
        ];
    }

    /**
     * Calculate total production from daily entries
     */
    private function calculateTotalProduction($dailyEntries)
    {
        $totalPieces = 0;

        foreach ($dailyEntries as $entry) {
            $eggData = $this->parseEggData($entry->daily_egg_production);
            $totalPieces += $eggData['total_pieces'];
        }

        $crates = floor($totalPieces / self::EGGS_PER_CRATE);
        $pieces = $totalPieces % self::EGGS_PER_CRATE;

        return [
            'crates' => $crates,
            'pieces' => $pieces,
            'total_pieces' => $totalPieces
        ];
    }

    /**
     * Calculate total broken eggs
     */
    private function calculateTotalBrokenEggs($dailyEntries)
    {
        return $dailyEntries->sum('broken_egg');
    }

    /**
     * Calculate average daily production per bird - FIXED VERSION
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
            
            $eggData = $this->parseEggData($entry->daily_egg_production);
            
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
            $eggData = $this->parseEggData($entry->daily_egg_production);
            
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

    /**
     * Validate and clean egg production data
     */
    private function validateEggProduction($dailyEntries)
    {
        $unrealisticEntries = [];
        
        foreach ($dailyEntries as $entry) {
            if ($entry->current_birds > 0) {
                $eggData = $this->parseEggData($entry->daily_egg_production);
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
     * Calculate flock-specific metrics
     */
    private function calculateFlockMetrics($flockId, $dailyEntries)
    {
        if ($flockId) {
            // Single flock selected
            $flock = Flock::find($flockId);
            if (!$flock) {
                return [
                    'totalBirds' => 0,
                    'currentBirds' => 0,
                    'totalMortality' => 0
                ];
            }
            
            $totalBirds = $flock->initial_bird_count;
            
            // Get latest current birds from this flock
            $latestEntry = $dailyEntries->sortByDesc('created_at')->first();
            $currentBirds = $latestEntry->current_birds ?? 0;
            
            // Calculate correct mortality: initial - current
            $totalMortality = max(0, $totalBirds - $currentBirds);
            
            return [
                'totalBirds' => $totalBirds,
                'currentBirds' => $currentBirds,
                'totalMortality' => $totalMortality
            ];
        } else {
            // All flocks combined
            $totalBirds = Flock::sum('initial_bird_count');
            
            // For all flocks, we need to get the latest current birds for each flock
            // Since we're combining data from both flocks, we need a different approach
            
            // Get all flocks
            $flocks = Flock::all();
            $currentBirds = 0;
            
            foreach ($flocks as $flock) {
                // Get latest entry for this flock
                $latestEntry = DailyEntry::whereHas('weekEntry', function($q) use ($flock) {
                    $q->where('flock_id', $flock->id);
                })->orderBy('created_at', 'desc')->first();
                
                if ($latestEntry) {
                    $currentBirds += $latestEntry->current_birds;
                }
            }
            
            $totalMortality = max(0, $totalBirds - $currentBirds);
            
            return [
                'totalBirds' => $totalBirds,
                'currentBirds' => $currentBirds,
                'totalMortality' => $totalMortality
            ];
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

        // CORRECTED: Calculate flock metrics (total birds, current birds, mortality)
        $flockMetrics = $this->calculateFlockMetrics($flockId, $dailyEntries);
        $totalBirds = $flockMetrics['totalBirds'];
        $currentBirds = $flockMetrics['currentBirds'];
        $totalMortality = $flockMetrics['totalMortality'];

        // Egg production calculations - in crates and pieces
        $productionData = $this->calculateTotalProduction($dailyEntries);
        $totalEggProductionCrates = $productionData['crates'];
        $totalEggProductionPieces = $productionData['pieces'];
        $totalEggProductionTotalPieces = $productionData['total_pieces'];
        
        // For backward compatibility
        $totalEggProduction = $totalEggProductionTotalPieces;

        // FIXED: Feed calculations - each daily_feeds unit = 1 BAG (not kg)
        $totalFeedBags = $dailyEntries->sum('daily_feeds');
        $totalFeedKg = $totalFeedBags * self::BAG_WEIGHT_KG; // Convert to kg for display
        
        // For backward compatibility
        $totalFeedConsumed = $totalFeedBags; // Now in bags, not kg

        // Egg Mortality = Broken Eggs
        $totalEggMortality = $this->calculateTotalBrokenEggs($dailyEntries);

        // Total sold eggs
        $soldEggData = $this->getTotalSoldEggs($dailyEntries);
        $totalEggsSoldCrates = $soldEggData['crates'];
        $totalEggsSoldPieces = $soldEggData['pieces'];
        $totalEggsSoldTotalPieces = $soldEggData['total_pieces'];
        
        // For backward compatibility
        $totalEggsSold = $totalEggsSoldTotalPieces;

        // FIXED: Production rate calculation - CORRECT CALCULATION
        $avgProductionRate = $this->calculateProductionRate($dailyEntries, $currentBirds);

        // Revenue calculation (assuming $0.05 per egg)
        $totalRevenue = $totalEggsSoldTotalPieces * 0.05;

        // Drug usage - count days with drugs administered
        $totalDrugUsage = $dailyEntries->where('drugs', '!=', 'Nil')
            ->where('drugs', '!=', '')
            ->whereNotNull('drugs')
            ->count();

        // Flock Capital Analysis - using bags for feed cost
        $capitalInvestment = $totalBirds * 2; // $2 per bird
        $feedCost = $totalFeedBags * 25; // $25 per bag
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
            $weekBroken = 0;
            
            foreach ($weekEntries as $entry) {
                $productionData = $this->parseEggData($entry->daily_egg_production);
                $salesData = $this->parseEggData($entry->daily_sold_egg);
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
                'feed_bags' => $weekEntries->sum('daily_feeds'), // Already in bags
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
            return $this->parseEggData($entry->daily_egg_production)['total_pieces'] > 0;
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
            'totalMortality', // CORRECTED: This is now initial - current, not sum(daily_mortality)
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