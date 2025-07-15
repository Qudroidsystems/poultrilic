<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Flock;
use App\Models\WeekEntry;
use App\Models\DailyEntry;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:dashboard', ['only' => ['index']]);
    }

    public function index()
    {
        $pagetitle = "Poultry Analytics";

        // Define date range (last 30 days)
        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // Total Birds
        $totalBirds = Flock::sum('initial_bird_count');

        // Current Birds
        $currentBirds = Flock::sum('current_bird_count');

        // Total Egg Production (in thousands)
        $totalEggProduction = DailyEntry::whereBetween('created_at', [$startDate, $endDate])
            ->sum('daily_egg_production') / 1000;

        // Total Mortality
        $totalMortality = DailyEntry::whereBetween('created_at', [$startDate, $endDate])
            ->sum('daily_mortality');

        // Feed Consumption (kg)
        $totalFeedConsumed = DailyEntry::whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_feeds_consumed');

        // Drug Usage (assuming drugs is a quantity or cost field)
        $totalDrugUsage = DailyEntry::whereBetween('created_at', [$startDate, $endDate])
            ->sum('drugs');

        // Eggs Sold and Revenue (assuming $0.05 per egg)
        $totalEggsSold = DailyEntry::whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_sold_egg');
        $totalRevenue = $totalEggsSold * 0.05; // Adjust price per egg as needed

        // Production Rate (average eggs per hen per day, as percentage)
        $avgProductionRate = $currentBirds > 0
            ? (DailyEntry::whereBetween('created_at', [$startDate, $endDate])
                ->avg('daily_egg_production') / $currentBirds) * 100
            : 0;

        // Egg Mortality (broken eggs)
        $totalEggMortality = DailyEntry::whereBetween('created_at', [$startDate, $endDate])
            ->sum('broken_egg');

        // Flock Capital Analysis
        $capitalInvestment = Flock::sum('initial_bird_count') * 2; // Assume $2 per bird
        $feedCost = $totalFeedConsumed * 0.5; // Assume $0.5 per kg of feed
        $drugCost = $totalDrugUsage * 1; // Assume $1 per unit of drug
        $laborCost = 1000; // Example fixed labor cost for 30 days
        $operationalExpenses = $feedCost + $drugCost + $laborCost;
        $netIncome = $totalRevenue - $operationalExpenses;
        $capitalValue = $netIncome > 0 ? $netIncome / 0.1 : 0; // Income Approach, 10% cap rate

        // Chart Data (weekly aggregates for last 30 days)
        $weeks = collect(range(0, 3))->map(function ($i) use ($startDate) {
            return $startDate->copy()->addWeeks($i)->format('W');
        });

        $feedChartData = DailyEntry::whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(\DB::raw('WEEK(created_at)'))
            ->selectRaw('WEEK(created_at) as week, SUM(total_feeds_consumed) as total')
            ->pluck('total', 'week')->toArray();

        $drugChartData = DailyEntry::whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(\DB::raw('WEEK(created_at)'))
            ->selectRaw('WEEK(created_at) as week, SUM(drugs) as total')
            ->pluck('total', 'week')->toArray();

        $eggProductionChartData = DailyEntry::whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(\DB::raw('WEEK(created_at)'))
            ->selectRaw('WEEK(created_at) as week, SUM(daily_egg_production) as total')
            ->pluck('total', 'week')->toArray();

        $eggSoldChartData = DailyEntry::whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(\DB::raw('WEEK(created_at)'))
            ->selectRaw('WEEK(created_at) as week, SUM(total_sold_egg) as total')
            ->pluck('total', 'week')->toArray();

        $productionRateChartData = DailyEntry::whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(\DB::raw('WEEK(created_at)'))
            ->selectRaw('WEEK(created_at) as week, AVG(daily_egg_production / current_birds) * 100 as rate')
            ->pluck('rate', 'week')->toArray();

        $eggMortalityChartData = DailyEntry::whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(\DB::raw('WEEK(created_at)'))
            ->selectRaw('WEEK(created_at) as week, SUM(broken_egg) as total')
            ->pluck('total', 'week')->toArray();

        return view('dashboards.dashboard', compact(
            'pagetitle',
            'totalBirds',
            'currentBirds',
            'totalEggProduction',
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
            'weeks',
            'feedChartData',
            'drugChartData',
            'eggProductionChartData',
            'eggSoldChartData',
            'productionRateChartData',
            'eggMortalityChartData'
        ));
    }
}