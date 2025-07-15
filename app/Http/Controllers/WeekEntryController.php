<?php

namespace App\Http\Controllers;

use App\Models\Flock;
use App\Models\WeekEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WeekEntryController extends Controller
{

    function __construct()
    {
         $this->middleware('permission:View weekly-entry|Create weekly-entry|Update weekly-entry|Delete weekly-entry', ['only' => ['index','store']]);
         $this->middleware('permission:Create weekly-entry', ['only' => ['create','store']]);
         $this->middleware('permission:Update weekly-entry', ['only' => ['edit','update']]);
         $this->middleware('permission:Delete weekly-entry', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of week entries for a flock with pagination and filtering.
     */
    public function index(Request $request, $flockId)
    {
        try {
            $pagetitle = "Week Entry Management";
            $flock = Flock::with('weekEntries.dailyEntries')->findOrFail($flockId);

            // Debug flock name
            $flockName = $flock->name ?? 'Flock ' . $flock->id;
            Log::info('Flock name: ' . $flockName);
            if ($request->query('debug')) {
                return response()->json(['flock_name' => $flockName]);
            }

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

            if ($request->ajax()) {
                return response()->json([
                    'weekEntries' => $weekEntries->items(),
                    'pagination' => (string) $weekEntries->links(),
                    'total' => $weekEntries->total(),
                    'chartData' => $chartData,
                ]);
            }

            return view('flocks.weeks.index', compact('flock', 'weekEntries', 'chartData', 'pagetitle'));
        } catch (\Exception $e) {
            Log::error('Error in week index: ' . $e->getMessage(), [
                'flock_id' => $flockId,
                'request' => $request->all(),
            ]);
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request, $flockId)
    {
        try {
           // $this->authorize('create weekly-entry');

            Log::info('Store week entry request', [
                'flock_id' => $flockId,
                'request_data' => $request->all(),
            ]);

            $validated = $request->validate([
                'week_number' => 'required|integer|min:1',
            ]);

            $flock = Flock::findOrFail($flockId);
            $lastWeek = $flock->weekEntries()->latest()->first();

            if ($lastWeek && $lastWeek->dailyEntries()->count() < 7) {
                Log::warning('Previous week incomplete', [
                    'flock_id' => $flockId,
                    'last_week_id' => $lastWeek->id,
                    'daily_entries_count' => $lastWeek->dailyEntries()->count(),
                ]);
                return response()->json([
                    'message' => 'Previous week is not complete. Please complete 7 daily entries first.'
                ], 422);
            }

            $week = WeekEntry::create([
                'flock_id' => $flock->id,
                'week_name' => 'Week ' . $validated['week_number'],
                'week_number' => $validated['week_number'],
            ]);

            Log::info('Week entry created', [
                'week_id' => $week->id,
                'week_name' => $week->week_name,
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
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Failed to store week entry: ' . $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Show the form to create a new week for a flock (for AJAX modal).
     */
    public function create($flockId)
    {
        $this->authorize('Create weekly-entry');

        $flock = Flock::findOrFail($flockId);
        $lastWeek = $flock->weekEntries()->latest()->first();

        if ($lastWeek && $lastWeek->dailyEntries()->count() < 7) {
            return response()->json([
                'message' => 'You must complete all 7 days of the previous week before creating a new week.'
            ], 422);
        }

        return response()->json(['message' => 'Ready to create a new week']);
    }

    /**
     * Store a new weekly entry.
     */
   
    /**
     * Update a week entry.
     */
    public function update(Request $request, $flockId, $weekId)
    {
        $this->authorize('Update weekly-entry');

        $request->validate([
            'week_number' => 'required|integer|min:1',
        ]);

        $week = WeekEntry::where('flock_id', $flockId)->findOrFail($weekId);
        $week->update([
            'week_name' => 'Week ' . $request->week_number,
            'week_number' => $request->week_number,
        ]);

        return response()->json([
            'id' => $week->id,
            'week_name' => $week->week_name,
            'daily_entries_count' => $week->dailyEntries()->count(),
            'created_at' => $week->created_at->format('Y-m-d'),
        ]);
    }

    /**
     * Delete a week entry.
     */
    public function destroy($flockId, $weekId)
    {
        $this->authorize('Delete weekly-entry');

        $week = WeekEntry::where('flock_id', $flockId)->findOrFail($weekId);
        $week->delete();

        return response()->json(['message' => 'Week deleted successfully']);
    }

    public function bulkDestroy(Request $request, $flockId)
    {
        $this->authorize('Delete weekly-entry');
        $request->validate(['week_ids' => 'required|array']);
        WeekEntry::where('flock_id', $flockId)->whereIn('id', $request->week_ids)->delete();
        return response()->json(['message' => 'Weeks deleted successfully']);
    }
}