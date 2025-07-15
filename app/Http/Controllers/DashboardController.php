<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Flock;
use App\Models\DailyEntry;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PoultryAnalyticsExport;
use Barryvdh\DomPDF\Facade\Pdf;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:dashboard', ['only' => ['index', 'export']]);
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
        $query = $flockId
            ? DailyEntry::whereHas('weekEntry', fn($q) => $q->where('flock_id', $flockId))
            : DailyEntry::query();

        // Key Metrics
        $totalBirds = $flockId
            ? Flock::where('id', $flockId)->sum('initial_bird_count')
            : Flock::sum('initial_bird_count');
        $currentBirds = $flockId
            ? Flock::where('id', $flockId)->sum('current_bird_count')
            : Flock::sum('current_bird_count');
        $totalEggProduction = $query->whereBetween('created_at', [$startDate, $endDate])
            ->sum('daily_egg_production') / 1000;
        $totalMortality = $query->whereBetween('created_at', [$startDate, $endDate])
            ->sum('daily_mortality');
        $totalFeedConsumed = $query->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_feeds_consumed');
        $totalDrugUsage = $query->whereBetween('created_at', [$startDate, $endDate])
            ->sum('drugs');
        $totalEggsSold = $query->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_sold_egg');
        $totalRevenue = $totalEggsSold * 0.05; // $0.05 per egg
        $avgProductionRate = $currentBirds > 0
            ? ($query->whereBetween('created_at', [$startDate, $endDate])
                ->avg('daily_egg_production') / $currentBirds) * 100
            : 0;
        $totalEggMortality = $query->whereBetween('created_at', [$startDate, $endDate])
            ->sum('broken_egg');

        // Flock Capital Analysis
        $capitalInvestment = $flockId
            ? Flock::where('id', $flockId)->sum('initial_bird_count') * 2
            : Flock::sum('initial_bird_count') * 2; // $2 per bird
        $feedCost = $totalFeedConsumed * 0.5; // $0.5 per kg
        $drugCost = $totalDrugUsage * 1; // $1 per unit
        $laborCost = 1000; // Fixed for 30 days
        $operationalExpenses = $feedCost + $drugCost + $laborCost;
        $netIncome = $totalRevenue - $operationalExpenses;
        $capitalValue = $netIncome > 0 ? $netIncome / 0.1 : 0; // 10% cap rate

        // Chart Data
        $weeks = collect(range(0, 3))->map(function ($i) use ($startDate) {
            return $startDate->copy()->addWeeks($i)->format('W');
        })->toArray();

        $feedChartData = array_fill(0, 4, 0);
        $drugChartData = array_fill(0, 4, 0);
        $eggProductionChartData = array_fill(0, 4, 0);
        $eggSoldChartData = array_fill(0, 4, 0);
        $productionRateChartData = array_fill(0, 4, 0);
        $eggMortalityChartData = array_fill(0, 4, 0);

        $weeklyData = $query->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(\DB::raw('WEEK(created_at)'))
            ->selectRaw('
                WEEK(created_at) as week,
                SUM(total_feeds_consumed) as feed,
                SUM(drugs) as drugs,
                SUM(daily_egg_production) as eggs_produced,
                SUM(total_sold_egg) as eggs_sold,
                AVG(daily_egg_production / NULLIF(current_birds, 0)) * 100 as production_rate,
                SUM(broken_egg) as egg_mortality
            ')
            ->get();

        foreach ($weeklyData as $entry) {
            $index = array_search($entry->week, $weeks);
            if ($index !== false) {
                $feedChartData[$index] = (float) $entry->feed;
                $drugChartData[$index] = (float) $entry->drugs;
                $eggProductionChartData[$index] = (float) $entry->eggs_produced;
                $eggSoldChartData[$index] = (float) $entry->eggs_sold;
                $productionRateChartData[$index] = (float) $entry->production_rate;
                $eggMortalityChartData[$index] = (float) $entry->egg_mortality;
            }
        }

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