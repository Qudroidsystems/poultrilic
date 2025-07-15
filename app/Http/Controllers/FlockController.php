<?php

namespace App\Http\Controllers;

use App\Models\Flock;
use App\Models\WeekEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class FlockController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View flock|Create flock|Update flock|Delete flock', ['only' => ['index', 'store']]);
        $this->middleware('permission:Create flock', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update flock', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete flock', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Flock::query();

            if ($request->has('search') && $request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('initial_bird_count', 'like', '%' . $request->search . '%')
                      ->orWhere('current_bird_count', 'like', '%' . $request->search . '%')
                      ->orWhere('created_at', 'like', '%' . $request->search . '%');
                });
            }

            if ($request->has('bird_count_filter') && $request->bird_count_filter !== 'all') {
                $range = explode('-', $request->bird_count_filter);
                if (count($range) === 2) {
                    $query->whereBetween('initial_bird_count', [$range[0], $range[1]]);
                } elseif ($range[0] === '501+') {
                    $query->where('initial_bird_count', '>', 500);
                }
            }

            $flocks = $query->with('weekEntries.dailyEntries')->paginate(5);

            $bird_count_ranges = [
                '0-100' => Flock::where('initial_bird_count', '<=', 100)->count(),
                '101-200' => Flock::whereBetween('initial_bird_count', [101, 200])->count(),
                '201-500' => Flock::whereBetween('initial_bird_count', [201, 500])->count(),
                '501+' => Flock::where('initial_bird_count', '>', 500)->count(),
            ];

            $allFlocksStats = [
                'total_flocks' => Flock::count(),
                'total_initial_bird_count' => Flock::sum('initial_bird_count'),
                'total_current_bird_count' => Flock::sum('current_bird_count'),
                'avg_initial_bird_count' => Flock::avg('initial_bird_count') ?? 0,
                'avg_current_bird_count' => Flock::avg('current_bird_count') ?? 0,
                'total_weeks' => WeekEntry::count(),
                'total_daily_entries' => WeekEntry::withCount('dailyEntries')->get()->sum('daily_entries_count'),
                'total_egg_production' => WeekEntry::join('daily_entries', 'week_entries.id', '=', 'daily_entries.week_entry_id')
                    ->sum('daily_entries.daily_egg_production'),
                'total_mortality' => WeekEntry::join('daily_entries', 'week_entries.id', '=', 'daily_entries.week_entry_id')
                    ->sum('daily_entries.daily_mortality'),
                'total_feeds_consumed' => WeekEntry::join('daily_entries', 'week_entries.id', '=', 'daily_entries.week_entry_id')
                    ->sum('daily_entries.daily_feeds'),
            ];

            if ($request->ajax()) {
                return response()->json([
                    'flocks' => $flocks->items()->map(function ($flock) {
                        return [
                            'id' => $flock->id,
                            'initial_bird_count' => $flock->initial_bird_count,
                            'current_bird_count' => $flock->current_bird_count,
                            'created_at' => $flock->created_at->format('Y-m-d'),
                        ];
                    }),
                    'pagination' => (string) $flocks->links(),
                    'total' => $flocks->total(),
                    'bird_count_ranges' => $bird_count_ranges,
                    'allFlocksStats' => $allFlocksStats,
                ]);
            }

            return view('flocks.flocks.index', [
                'flocks' => $flocks,
                'bird_count_ranges' => $bird_count_ranges,
                'allFlocksStats' => $allFlocksStats,
                'pagetitle' => 'Flock Management | Flocks',
            ]);
        } catch (\Exception $e) {
            Log::error('Error in flock index: ' . $e->getMessage(), [
                'request' => $request->all(),
            ]);
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function getWeekEntries(Request $request, $flockId)
    {
        try {
            $this->authorize('View flock');
            $flock = Flock::with('weekEntries.dailyEntries')->findOrFail($flockId);
            $weekEntries = $flock->weekEntries()->withCount('dailyEntries')->latest()->get();

            $weekChartData = [
                'labels' => $weekEntries->pluck('week_name')->toArray(),
                'daily_entries_counts' => $weekEntries->pluck('daily_entries_count')->toArray(),
            ];

            $flockStats = [
                'total_weeks' => $weekEntries->count(),
                'total_daily_entries' => $weekEntries->sum('daily_entries_count'),
                'total_egg_production' => $weekEntries->sum(function ($week) {
                    return $week->dailyEntries->sum('daily_egg_production');
                }),
                'total_mortality' => $weekEntries->sum(function ($week) {
                    return $week->dailyEntries->sum('daily_mortality');
                }),
                'total_feeds_consumed' => $weekEntries->sum(function ($week) {
                    return $week->dailyEntries->sum('daily_feeds');
                }),
                'avg_daily_egg_production' => $weekEntries->sum('daily_entries_count') > 0
                    ? $weekEntries->sum(function ($week) {
                        return $week->dailyEntries->sum('daily_egg_production');
                    }) / $weekEntries->sum('daily_entries_count')
                    : 0,
                'avg_daily_mortality' => $weekEntries->sum('daily_entries_count') > 0
                    ? $weekEntries->sum(function ($week) {
                        return $week->dailyEntries->sum('daily_mortality');
                    }) / $weekEntries->sum('daily_entries_count')
                    : 0,
                'avg_daily_feeds' => $weekEntries->sum('daily_entries_count') > 0
                    ? $weekEntries->sum(function ($week) {
                        return $week->dailyEntries->sum('daily_feeds');
                    }) / $weekEntries->sum('daily_entries_count')
                    : 0,
            ];

            return response()->json([
                'weekEntries' => $weekEntries->map(function ($week) {
                    return [
                        'id' => $week->id,
                        'week_name' => $week->week_name,
                        'daily_entries_count' => $week->daily_entries_count,
                        'created_at' => $week->created_at->format('Y-m-d'),
                    ];
                }),
                'weekChartData' => $weekChartData,
                'flockStats' => $flockStats,
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::error('Authorization error fetching week entries: ' . $e->getMessage(), [
                'flock_id' => $flockId,
            ]);
            return response()->json(['error' => 'You are not authorized to view week entries'], 403);
        } catch (ModelNotFoundException $e) {
            Log::error('Flock not found: ' . $e->getMessage(), [
                'flock_id' => $flockId,
            ]);
            return response()->json(['error' => 'Flock not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching week entries: ' . $e->getMessage(), [
                'flock_id' => $flockId,
            ]);
            return response()->json(['error' => 'Failed to fetch week entries: ' . $e->getMessage()], 500);
        }
    }

    public function create()
    {
        return view('flocks.create', [
            'pagetitle' => 'Flock Management | Create Flock',
        ]);
    }

    public function store(Request $request)
    {
        try {
            $this->authorize('Create flock');
            $validator = Validator::make($request->all(), [
                'initial_bird_count' => 'required|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $flock = Flock::create([
                'initial_bird_count' => $request->initial_bird_count,
                'current_bird_count' => $request->initial_bird_count,
            ]);

            return response()->json([
                'message' => 'Flock added successfully',
                'id' => $flock->id,
                'initial_bird_count' => $flock->initial_bird_count,
                'current_bird_count' => $flock->current_bird_count,
                'created_at' => $flock->created_at->format('Y-m-d'),
            ], 201);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::error('Authorization error storing flock: ' . $e->getMessage());
            return response()->json(['message' => 'You are not authorized to create a flock'], 403);
        } catch (\Exception $e) {
            Log::error('Error storing flock: ' . $e->getMessage(), [
                'request_data' => $request->all(),
            ]);
            return response()->json(['message' => 'Failed to store flock: ' . $e->getMessage()], 500);
        }
    }

    public function show(string $id)
    {
        try {
            $this->authorize('View flock');
            $flock = Flock::with('weekEntries.dailyEntries')->findOrFail($id);
            return view('flocks.show', [
                'flock' => $flock,
                'page_title' => 'Flock Management | View Flock #' . $flock->id,
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::error('Authorization error viewing flock: ' . $e->getMessage(), [
                'flock_id' => $id,
            ]);
            return response()->json(['message' => 'You are not authorized to view this flock'], 403);
        } catch (ModelNotFoundException $e) {
            Log::error('Flock not found: ' . $e->getMessage(), [
                'flock_id' => $id,
            ]);
            return response()->json(['message' => 'Flock not found'], 404);
        }
    }

    public function edit(string $id)
    {
        try {
            $this->authorize('Update flock');
            $flock = Flock::findOrFail($id);
            return view('flocks.edit', [
                'flock' => $flock,
                'page_title' => 'Flock Management | Edit Flock #' . $flock->id,
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::error('Authorization error editing flock: ' . $e->getMessage(), [
                'flock_id' => $id,
            ]);
            return response()->json(['message' => 'You are not authorized to edit this flock'], 403);
        } catch (ModelNotFoundException $e) {
            Log::error('Flock not found: ' . $e->getMessage(), [
                'flock_id' => $id,
            ]);
            return response()->json(['message' => 'Flock not found'], 404);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            $this->authorize('Update flock');
            $flock = Flock::findOrFail($id);
            $validator = Validator::make($request->all(), [
                'initial_bird_count' => 'required|integer|min:0',
                'current_bird_count' => 'required|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $flock->update([
                'initial_bird_count' => $request->initial_bird_count,
                'current_bird_count' => $request->current_bird_count,
            ]);

            return response()->json([
                'message' => 'Flock updated successfully',
                'id' => $flock->id,
                'initial_bird_count' => $flock->initial_bird_count,
                'current_bird_count' => $flock->current_bird_count,
                'created_at' => $flock->created_at->format('Y-m-d'),
            ], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::error('Authorization error updating flock: ' . $e->getMessage(), [
                'flock_id' => $id,
            ]);
            return response()->json(['message' => 'You are not authorized to update this flock'], 403);
        } catch (ModelNotFoundException $e) {
            Log::error('Flock not found: ' . $e->getMessage(), [
                'flock_id' => $id,
            ]);
            return response()->json(['message' => "Flock ID $id not found"], 404);
        } catch (\Exception $e) {
            Log::error('Error updating flock: ' . $e->getMessage(), [
                'flock_id' => $id,
                'request_data' => $request->all(),
            ]);
            return response()->json(['message' => 'Failed to update flock: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->authorize('Delete flock');
            $flock = Flock::findOrFail($id);
            $flock->delete();

            return response()->json([
                'message' => 'Flock deleted successfully',
            ], 200);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::error('Authorization error deleting flock: ' . $e->getMessage(), [
                'flock_id' => $id,
            ]);
            return response()->json(['message' => 'You are not authorized to delete this flock'], 403);
        } catch (ModelNotFoundException $e) {
            Log::error('Flock not found: ' . $e->getMessage(), [
                'flock_id' => $id,
            ]);
            return response()->json(['message' => "Flock ID $id not found"], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting flock: ' . $e->getMessage(), [
                'flock_id' => $id,
            ]);
            return response()->json(['message' => 'Failed to delete flock: ' . $e->getMessage()], 500);
        }
    }
}