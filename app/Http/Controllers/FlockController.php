<?php

namespace App\Http\Controllers;

use App\Models\Flock;
use App\Models\WeekEntry;
use App\Models\DailyEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FlockController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View flock', ['only' => ['index', 'show', 'weekEntries']]);
        $this->middleware('permission:Create flock', ['only' => ['store']]);
        $this->middleware('permission:Update flock', ['only' => ['update']]);
        $this->middleware('permission:Delete flock', ['only' => ['destroy']]);
    }
public function index(Request $request)
{
    $pagetitle = 'Flock Management';
    $perPage = 5;
    $search = $request->input('search', '');
    $birdCountFilter = $request->input('bird_count_filter', 'all');
    $statusFilter = $request->input('status_filter', 'all');

    $query = Flock::query();

    if ($search) {
        $query->where('id', 'like', "%{$search}%")
              ->orWhere('initial_bird_count', 'like', "%{$search}%");
    }

    if ($birdCountFilter !== 'all') {
        $ranges = [
            '0-100' => [0, 100],
            '101-200' => [101, 200],
            '201-500' => [201, 500],
            '501+' => [501, PHP_INT_MAX]
        ];
        if (isset($ranges[$birdCountFilter])) {
            $query->whereBetween('initial_bird_count', $ranges[$birdCountFilter]);
        }
    }

    if ($statusFilter !== 'all') {
        $query->where('status', $statusFilter);
    }

    $flocks = $query->paginate($perPage);

    $allFlocksStats = [
        'total_flocks' => Flock::count(),
        'total_initial_bird_count' => Flock::sum('initial_bird_count'),
        'total_current_bird_count' => Flock::sum('current_bird_count'),
        'avg_initial_bird_count' => Flock::avg('initial_bird_count') ?? 0,
        'avg_current_bird_count' => Flock::avg('current_bird_count') ?? 0,
        'total_weeks' => WeekEntry::distinct('week_name')->count(),
        'total_daily_entries' => DailyEntry::count(),
        'total_egg_production' => DailyEntry::sum('daily_egg_production'),
        'total_mortality' => DailyEntry::sum('daily_mortality'),
        'total_feeds_consumed' => DailyEntry::sum('total_feeds_consumed')
    ];

    if ($request->ajax()) {
        return response()->json([
            'flocks' => $flocks->map(function ($flock) {
                return [
                    'id' => $flock->id,
                    'initial_bird_count' => $flock->initial_bird_count,
                    'current_bird_count' => $flock->current_bird_count,
                    'status' => $flock->status,
                    'status_formatted' => ucfirst($flock->status),
                    'created_at' => $flock->created_at->format('Y-m-d')
                ];
            })->toArray(),
            'pagination' => $flocks->links()->toHtml(),
            'total' => $flocks->total(),
            'allFlocksStats' => $allFlocksStats
        ]);
    }

    return view('flocks.flocks.index', compact('pagetitle','flocks', 'allFlocksStats'));
}

public function store(Request $request)
{
    try {
        Log::info('Store request received', $request->all());

        $validated = $request->validate([
            'initial_bird_count' => 'required|integer|min:0',
            'current_bird_count' => 'required|integer|min:0',
            'status' => 'required|in:active,inactive,sold,ended'
        ]);

        Log::info('Validated data', $validated);

        $flock = Flock::create($validated);

        Log::info('Flock created', ['id' => $flock->id]);

        return response()->json([
            'id' => $flock->id,
            'initial_bird_count' => $flock->initial_bird_count,
            'current_bird_count' => $flock->current_bird_count,
            'status' => $flock->status,
            'created_at' => $flock->created_at->toIso8601String()
        ], 201);
    } catch (\Exception $e) {
        Log::error('Error creating flock: ' . $e->getMessage());
        return response()->json(['message' => 'Failed to create flock: ' . $e->getMessage()], 500);
    }
}

public function update(Request $request, Flock $flock)
{
    $validated = $request->validate([
        'initial_bird_count' => 'required|integer|min:0',
        'current_bird_count' => 'required|integer|min:0',
        'status' => 'required|in:active,inactive,sold,ended'
    ]);

    $flock->update($validated);

    return response()->json([
        'id' => $flock->id,
        'initial_bird_count' => $flock->initial_bird_count,
        'current_bird_count' => $flock->current_bird_count,
        'status' => $flock->status,
        'created_at' => $flock->created_at->toIso8601String()
    ]);
}

    public function destroy(Flock $flock)
    {
        $flock->delete();
        return response()->json(['message' => 'Flock deleted successfully']);
    }

   public function weekEntries(Request $request, $flockId)
{
    $flock = Flock::findOrFail($flockId);
    $weekEntries = WeekEntry::where('flock_id', $flockId)->get();

    $labels = $weekEntries->pluck('week')->unique()->values();
    $dailyEntriesCounts = $weekEntries->groupBy('week')->map(function ($group) {
        return $group->sum('daily_entries_count');
    })->values();

    $flockStats = [
        'total_weeks' => $weekEntries->count(),
        'total_daily_entries' => DailyEntry::whereIn('week_entry_id', $weekEntries->pluck('id'))->count(),
        'total_egg_production' => DailyEntry::whereIn('week_entry_id', $weekEntries->pluck('id'))->sum('daily_egg_production'),
        'total_mortality' => DailyEntry::whereIn('week_entry_id', $weekEntries->pluck('id'))->sum('daily_mortality'),
        'total_feeds_consumed' => DailyEntry::whereIn('week_entry_id', $weekEntries->pluck('id'))->sum('total_feeds_consumed'),
        'avg_daily_egg_production' => DailyEntry::whereIn('week_entry_id', $weekEntries->pluck('id'))->avg('daily_egg_production') ?? 0,
        'avg_daily_mortality' => DailyEntry::whereIn('week_entry_id', $weekEntries->pluck('id'))->avg('daily_mortality') ?? 0,
        'avg_daily_feeds' => DailyEntry::whereIn('week_entry_id', $weekEntries->pluck('id'))->avg('total_feeds_consumed') ?? 0
    ];

    return response()->json([
        'weekChartData' => [
            'labels' => $labels->isEmpty() ? ['No Data'] : $labels,
            'daily_entries_counts' => $dailyEntriesCounts->isEmpty() ? [0] : $dailyEntriesCounts
        ],
        'flockStats' => $flockStats
    ]);
}
}
