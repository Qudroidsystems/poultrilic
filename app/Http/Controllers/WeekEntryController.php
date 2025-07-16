<?php

namespace App\Http\Controllers;

use App\Models\Flock;
use App\Models\WeekEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class WeekEntryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View weekly-entry|Create weekly-entry|Update weekly-entry|Delete weekly-entry', ['only' => ['index', 'store']]);
        $this->middleware('permission:Create weekly-entry', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update weekly-entry', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete weekly-entry', ['only' => ['destroy', 'bulkDestroy']]);
    }

    public function index(Request $request, $flockId)
    {
        try {
            $pagetitle = "Week Entry Management";
            $flock = Flock::with('weekEntries.dailyEntries')->findOrFail($flockId);

            $query = $flock->weekEntries()->withCount('dailyEntries');

            if ($request->has('search') && $request->search) {
                $query->where('week_name', 'like', '%' . $request->search . '%');
            }

            if ($request->has('week_filter') && $request->week_filter !== 'all') {
                $range = explode('-', $request->week_filter);
                if (count($range) === 2) {
                    $query->whereBetween('week_number', [$range[0], $range[1]]);
                } elseif ($range[0] === '31+') {
                    $query->where('week_number', '>=', 31);
                }
            }

            $weekEntries = $query->latest()->paginate(100);

            $chartData = [
                'labels' => $weekEntries->pluck('week_name')->toArray(),
                'daily_entries_counts' => $weekEntries->pluck('daily_entries_count')->toArray(),
            ];

            $allWeeksStats = [
                'total_daily_entries' => $flock->weekEntries()->withCount('dailyEntries')->get()->sum('daily_entries_count'),
                'total_egg_production' => $flock->weekEntries()->join('daily_entries', 'week_entries.id', '=', 'daily_entries.week_entry_id')
                    ->sum('daily_entries.daily_egg_production'),
                'total_mortality' => $flock->weekEntries()->join('daily_entries', 'week_entries.id', '=', 'daily_entries.week_entry_id')
                    ->sum('daily_entries.daily_mortality'),
                'total_feeds_consumed' => $flock->weekEntries()->join('daily_entries', 'week_entries.id', '=', 'daily_entries.week_entry_id')
                    ->sum('daily_entries.daily_feeds'),
                'avg_daily_egg_production' => $flock->weekEntries()->join('daily_entries', 'week_entries.id', '=', 'daily_entries.week_entry_id')
                    ->avg('daily_entries.daily_egg_production') ?? 0,
                'avg_daily_mortality' => $flock->weekEntries()->join('daily_entries', 'week_entries.id', '=', 'daily_entries.week_entry_id')
                    ->avg('daily_entries.daily_mortality') ?? 0,
                'avg_daily_feeds' => $flock->weekEntries()->join('daily_entries', 'week_entries.id', '=', 'daily_entries.week_entry_id')
                    ->avg('daily_entries.daily_feeds') ?? 0,
            ];

            if ($request->ajax()) {
                return response()->json([
                    'weekEntries' => $weekEntries->items()->map(function ($entry) {
                        return [
                            'id' => $entry->id,
                            'week_name' => $entry->week_name,
                            'daily_entries_count' => $entry->daily_entries_count,
                            'created_at' => $entry->created_at->format('Y-m-d'),
                        ];
                    }),
                    'pagination' => (string) $weekEntries->links(),
                    'total' => $weekEntries->total(),
                    'chartData' => $chartData,
                    'allWeeksStats' => $allWeeksStats,
                ]);
            }

            return view('flocks.weeks.index', compact('flock', 'weekEntries', 'chartData', 'allWeeksStats', 'pagetitle'));
        } catch (\Exception $e) {
            Log::error('Error in week index: ' . $e->getMessage(), [
                'flock_id' => $flockId,
                'request' => $request->all(),
            ]);
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function getDailyEntries(Request $request, $flockId, $weekId)
    {
        try {
           
            $week = WeekEntry::where('flock_id', $flockId)->with('dailyEntries')->findOrFail($weekId);

            $dailyEntries = $week->dailyEntries()->latest()->get();

            $dailyChartData = [
                'labels' => $dailyEntries->pluck('day_number')->map(fn($day) => "Day $day")->toArray(),
                'daily_egg_production' => $dailyEntries->pluck('daily_egg_production')->toArray(),
                'daily_mortality' => $dailyEntries->pluck('daily_mortality')->toArray(),
                'daily_feeds' => $dailyEntries->pluck('daily_feeds')->toArray(),
            ];

            $weekStats = [
                'total_egg_production' => $dailyEntries->sum('daily_egg_production'),
                'total_mortality' => $dailyEntries->sum('daily_mortality'),
                'total_feeds_consumed' => $dailyEntries->sum('daily_feeds'),
                'avg_daily_egg_production' => $dailyEntries->avg('daily_egg_production') ?? 0,
                'avg_daily_mortality' => $dailyEntries->avg('daily_mortality') ?? 0,
                'avg_daily_feeds' => $dailyEntries->avg('daily_feeds') ?? 0,
            ];

            return response()->json([
                'dailyEntries' => $dailyEntries->map(function ($entry) {
                    return [
                        'id' => $entry->id,
                        'day_number' => "Day $entry->day_number",
                        'daily_feeds' => $entry->daily_feeds,
                        'daily_mortality' => $entry->daily_mortality,
                        'daily_egg_production' => $entry->daily_egg_production,
                    ];
                }),
                'dailyChartData' => $dailyChartData,
                'weekStats' => $weekStats,
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::error('Authorization error fetching daily entries: ' . $e->getMessage(), [
                'flock_id' => $flockId,
                'week_id' => $weekId,
            ]);
            return response()->json(['error' => 'You are not authorized to view daily entries'], 403);
        } catch (\Exception $e) {
            Log::error('Error fetching daily entries: ' . $e->getMessage(), [
                'flock_id' => $flockId,
                'week_id' => $weekId,
            ]);
            return response()->json(['error' => 'Failed to fetch daily entries: ' . $e->getMessage()], 500);
        }
    }

  public function store(Request $request, $flockId)
{
    try {
        $validated = $request->validate([
            'week_number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('week_entries', 'week_name')->where(function ($query) use ($flockId, $request) {
                    return $query->where('flock_id', $flockId)
                                ->where('week_name', 'Week ' . $request->input('week_number'));
                }),
            ],
        ]);

        $flock = Flock::findOrFail($flockId);
        $lastWeek = $flock->weekEntries()->latest()->first();

        if ($lastWeek && $lastWeek->dailyEntries()->count() < 7) {
            return response()->json([
                'message' => 'Previous week is not complete. Please complete 7 daily entries first.'
            ], 422);
        }

        $week = WeekEntry::create([
            'flock_id' => $flock->id,
            'week_name' => 'Week ' . $validated['week_number'],
        ]);

        return response()->json([
            'id' => $week->id,
            'week_name' => $week->week_name,
            'daily_entries_count' => $week->dailyEntries()->count(),
            'created_at' => $week->created_at->format('Y-m-d'),
        ], 201);
    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('Validation error storing week entry: ' . $e->getMessage(), [
            'flock_id' => $flockId,
            'errors' => $e->errors(),
        ]);
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
        Log::error('Authorization error storing week entry: ' . $e->getMessage(), [
            'flock_id' => $flockId,
        ]);
        return response()->json([
            'message' => 'You are not authorized to create a week entry',
        ], 403);
    } catch (\Exception $e) {
        Log::error('Error storing week entry: ' . $e->getMessage(), [
            'flock_id' => $flockId,
            'request_data' => $request->all(),
        ]);
        return response()->json([
            'message' => 'Failed to store week entry: ' . $e->getMessage(),
        ], 500);
    }
}
    public function create($flockId)
    {
        try {
           
            $flock = Flock::findOrFail($flockId);
            $lastWeek = $flock->weekEntries()->latest()->first();

            if ($lastWeek && $lastWeek->dailyEntries()->count() < 7) {
                return response()->json([
                    'message' => 'You must complete all 7 days of the previous week before creating a new week.'
                ], 422);
            }

            return response()->json(['message' => 'Ready to create a new week']);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::error('Authorization error creating week entry: ' . $e->getMessage(), [
                'flock_id' => $flockId,
            ]);
            return response()->json([
                'message' => 'You are not authorized to create a week entry',
            ], 403);
        } catch (\Exception $e) {
            Log::error('Error creating week entry: ' . $e->getMessage(), [
                'flock_id' => $flockId,
            ]);
            return response()->json([
                'message' => 'Failed to prepare week creation: ' . $e->getMessage(),
            ], 500);
        }
    }

  public function update(Request $request, $flockId, $weekId)
{
    try {
        $validated = $request->validate([
            'week_number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('week_entries', 'week_name')->where(function ($query) use ($flockId, $request) {
                    return $query->where('flock_id', $flockId)
                                ->where('week_name', 'Week ' . $request->input('week_number'));
                })->ignore($weekId),
            ],
        ]);

        $week = WeekEntry::where('flock_id', $flockId)->findOrFail($weekId);
        $week->update([
            'week_name' => 'Week ' . $validated['week_number'],
        ]);

        return response()->json([
            'id' => $week->id,
            'week_name' => $week->week_name,
            'daily_entries_count' => $week->dailyEntries()->count(),
            'created_at' => $week->created_at->format('Y-m-d'),
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('Validation error updating week entry: ' . $e->getMessage(), [
            'flock_id' => $flockId,
            'week_id' => $weekId,
            'errors' => $e->errors(),
        ]);
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
        Log::error('Authorization error updating week entry: ' . $e->getMessage(), [
            'flock_id' => $flockId,
            'week_id' => $weekId,
        ]);
        return response()->json([
            'message' => 'You are not authorized to update this week entry',
        ], 403);
    } catch (\Exception $e) {
        Log::error('Error updating week entry: ' . $e->getMessage(), [
            'flock_id' => $flockId,
            'week_id' => $weekId,
        ]);
        return response()->json([
            'message' => 'Failed to update week entry: ' . $e->getMessage(),
        ], 500);
    }
}
    public function destroy($flockId, $weekId)
    {
        try {
            

            $week = WeekEntry::where('flock_id', $flockId)->findOrFail($weekId);
            $week->delete();

            return response()->json(['message' => 'Week deleted successfully']);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::error('Authorization error deleting week entry: ' . $e->getMessage(), [
                'flock_id' => $flockId,
                'week_id' => $weekId,
            ]);
            return response()->json([
                'message' => 'You are not authorized to delete this week entry',
            ], 403);
        } catch (\Exception $e) {
            Log::error('Error deleting week entry: ' . $e->getMessage(), [
                'flock_id' => $flockId,
                'week_id' => $weekId,
            ]);
            return response()->json([
                'message' => 'Failed to delete week entry: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function bulkDestroy(Request $request, $flockId)
    {
        try {
            

            $validated = $request->validate(['week_ids' => 'required|array']);
            WeekEntry::where('flock_id', $flockId)->whereIn('id', $validated['week_ids'])->delete();

            return response()->json(['message' => 'Weeks deleted successfully']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error deleting week entries: ' . $e->getMessage(), [
                'flock_id' => $flockId,
                'errors' => $e->errors(),
            ]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::error('Authorization error deleting week entries: ' . $e->getMessage(), [
                'flock_id' => $flockId,
            ]);
            return response()->json([
                'message' => 'You are not authorized to delete week entries',
            ], 403);
        } catch (\Exception $e) {
            Log::error('Error deleting week entries: ' . $e->getMessage(), [
                'flock_id' => $flockId,
                'week_ids' => $request->week_ids,
            ]);
            return response()->json([
                'message' => 'Failed to delete week entries: ' . $e->getMessage(),
            ], 500);
        }
    }
}