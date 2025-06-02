<?php

namespace App\Http\Controllers;

use App\Models\DailyEntry;
use App\Models\WeekEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DailyEntryController extends Controller
{
    // Calculate cumulative totals for the flock
    protected function getFlockTotals($flockId)
    {
        $totals = DailyEntry::whereHas('weekEntry', function ($query) use ($flockId) {
            $query->where('flock_id', $flockId);
        })->selectRaw('
            SUM(total_feeds_consumed) as total_feeds_consumed,
            SUM(total_mortality) as total_mortality,
            SUM(daily_egg_production) as daily_egg_production,
            SUM(total_sold_egg) as total_sold_egg,
            SUM(broken_egg) as broken_egg,
            SUM(total_egg_in_farm) as total_egg_in_farm
        ')->first();

        return [
            'total_feeds_consumed' => $totals->total_feeds_consumed ?? 0,
            'total_mortality' => $totals->total_mortality ?? 0,
            'daily_egg_production' => $totals->daily_egg_production ?? 0,
            'total_sold_egg' => $totals->total_sold_egg ?? 0,
            'broken_egg' => $totals->broken_egg ?? 0,
            'total_egg_in_farm' => $totals->total_egg_in_farm ?? 0,
        ];
    }

    public function index(Request $request, $weekId)
    {
        try {
            Log::info('DailyEntryController::index called', ['week_id' => $weekId]);
            $pagetitle = "Daily Entry Management";
            $week = WeekEntry::with('dailyEntries')->findOrFail($weekId);
            $flock = $week->flock;

            $query = $week->dailyEntries();

            if ($request->has('search') && $request->search) {
                $query->where('day_number', 'like', '%' . $request->search . '%');
            }

            if ($request->has('day_filter') && $request->day_filter !== 'all') {
                $query->where('day_number', $request->day_filter);
            }

            $dailyEntries = $query->latest()->paginate(100);

            $chartData = [
                'labels' => $dailyEntries->pluck('day_number')->map(fn($day) => "Day $day")->toArray(),
                'daily_egg_production' => $dailyEntries->pluck('daily_egg_production')->toArray(),
            ];

            if ($request->ajax()) {
                Log::info('DailyEntryController::index AJAX response', ['week_id' => $weekId]);
                return response()->json([
                    'dailyEntries' => $dailyEntries->map(function ($entry) {
                        return [
                            'id' => $entry->id,
                            'day_number' => "Day $entry->day_number",
                            'daily_feeds' => $entry->daily_feeds,
                            'daily_mortality' => $entry->daily_mortality,
                            'current_birds' => $entry->current_birds,
                            'daily_egg_production' => $entry->daily_egg_production,
                            'created_at' => $entry->created_at->format('Y-m-d'),
                        ];
                    })->toArray(),
                    'pagination' => (string) $dailyEntries->links(),
                    'total' => $dailyEntries->total(),
                    'chartData' => $chartData,
                ]);
            }

            $flockTotals = $this->getFlockTotals($flock->id); // Pass totals to view
            return view('flocks.daily.index', compact('week', 'flock', 'dailyEntries', 'chartData', 'pagetitle', 'flockTotals'));
        } catch (\Exception $e) {
            Log::error('Error in DailyEntryController::index: ' . $e->getMessage(), [
                'week_id' => $weekId,
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function create($weekId)
    {
        try {
            Log::info('DailyEntryController::create called', ['week_id' => $weekId]);
            $week = WeekEntry::findOrFail($weekId);
            $flock = $week->flock;
            $flockTotals = $this->getFlockTotals($flock->id); // Pass totals to view
            return view('flocks.daily.create', compact('week', 'flock', 'flockTotals'));
        } catch (\Exception $e) {
            Log::error('Error in DailyEntryController::create: ' . $e->getMessage(), [
                'week_id' => $weekId,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to load create view: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request, $weekId)
    {
        try {
            Log::info('DailyEntryController::store called', [
                'week_id' => $weekId,
                'headers' => $request->headers->all(),
                'request_data' => $request->all(),
            ]);

            $week = WeekEntry::findOrFail($weekId);
            $flock = $week->flock;

            // Validate only editable fields
            $validated = $request->validate([
                'day_number' => 'required|integer|between:1,7|unique:daily_entries,day_number,NULL,id,week_entry_id,' . $weekId,
                'daily_feeds' => 'required|numeric|min:0',
                'available_feeds' => 'required|numeric|min:0',
                'daily_mortality' => 'required|integer|min:0',
                'sick_bay' => 'required|integer|min:0',
                'daily_sold_egg' => 'required|integer|min:0',
                'outstanding_egg' => 'required|integer|min:0',
                'drugs' => 'nullable|string',
                'reorder_feeds' => 'nullable|numeric|min:0',
            ]);

            Log::info('Validation passed in DailyEntryController::store', ['validated_data' => $validated]);

            $lastBirdCount = DailyEntry::where('week_entry_id', $weekId)
                ->orderByDesc('day_number')
                ->first()
                ?->current_birds ?? $flock->current_bird_count;

            $newCount = $lastBirdCount - $validated['daily_mortality'];

            // Calculate cumulative totals
            $flockTotals = $this->getFlockTotals($flock->id);

            $entry = DailyEntry::create([
                'week_entry_id' => $weekId,
                'day_number' => $validated['day_number'],
                'daily_feeds' => $validated['daily_feeds'],
                'available_feeds' => $validated['available_feeds'],
                'total_feeds_consumed' => $flockTotals['total_feeds_consumed'] + $validated['daily_feeds'],
                'daily_mortality' => $validated['daily_mortality'],
                'sick_bay' => $validated['sick_bay'],
                'total_mortality' => $flockTotals['total_mortality'] + $validated['daily_mortality'],
                'current_birds' => $newCount,
                'daily_egg_production' => $flockTotals['daily_egg_production'],
                'daily_sold_egg' => $validated['daily_sold_egg'],
                'total_sold_egg' => $flockTotals['total_sold_egg'] + $validated['daily_sold_egg'],
                'broken_egg' => $flockTotals['broken_egg'],
                'outstanding_egg' => $validated['outstanding_egg'],
                'total_egg_in_farm' => $flockTotals['total_egg_in_farm'],
                'drugs' => $validated['drugs'],
                'reorder_feeds' => $validated['reorder_feeds'],
            ]);

            $flock->update(['current_bird_count' => $newCount]);

            Log::info('Daily entry created', [
                'entry_id' => $entry->id,
                'week_id' => $weekId,
                'new_bird_count' => $newCount,
            ]);

            return response()->json([
                'id' => $entry->id,
                'day_number' => "Day $entry->day_number",
                'daily_feeds' => $entry->daily_feeds,
                'daily_mortality' => $entry->daily_mortality,
                'current_birds' => $entry->current_birds,
                'daily_egg_production' => $entry->daily_egg_production,
                'created_at' => $entry->created_at->format('Y-m-d'),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in DailyEntryController::store: ' . $e->getMessage(), [
                'week_id' => $weekId,
                'errors' => $e->errors(),
            ]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in DailyEntryController::store: ' . $e->getMessage(), [
                'week_id' => $weekId,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Failed to store daily entry: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $weekId, $id)
    {
        try {
            Log::info('DailyEntryController::update called', [
                'week_id' => $weekId,
                'entry_id' => $id,
                'request_data' => $request->all(),
            ]);

            $week = WeekEntry::findOrFail($weekId);
            $flock = $week->flock;

            // Validate only editable fields
            $validated = $request->validate([
                'day_number' => 'required|integer|between:1,7|unique:daily_entries,day_number,' . $id . ',id,week_entry_id,' . $weekId,
                'daily_feeds' => 'required|numeric|min:0',
                'available_feeds' => 'required|numeric|min:0',
                'daily_mortality' => 'required|integer|min:0',
                'sick_bay' => 'required|integer|min:0',
                'daily_sold_egg' => 'required|integer|min:0',
                'outstanding_egg' => 'required|integer|min:0',
                'drugs' => 'nullable|string',
                'reorder_feeds' => 'nullable|numeric|min:0',
            ]);

            Log::info('Validation passed in DailyEntryController::update', ['validated_data' => $validated]);

            $entry = DailyEntry::where('week_entry_id', $weekId)->findOrFail($id);

            $lastBirdCount = DailyEntry::where('week_entry_id', $weekId)
                ->where('day_number', '<', $validated['day_number'])
                ->orderByDesc('day_number')
                ->first()
                ?->current_birds ?? $flock->current_bird_count;

            $newCount = $lastBirdCount - $validated['daily_mortality'];

            // Calculate cumulative totals, excluding current entry to avoid double-counting
            $flockTotals = $this->getFlockTotals($flock->id);
            $currentEntryTotals = [
                'total_feeds_consumed' => $entry->total_feeds_consumed,
                'total_mortality' => $entry->total_mortality,
                'daily_egg_production' => $entry->daily_egg_production,
                'total_sold_egg' => $entry->total_sold_egg,
                'broken_egg' => $entry->broken_egg,
                'total_egg_in_farm' => $entry->total_egg_in_farm,
            ];

            $entry->update([
                'day_number' => $validated['day_number'],
                'daily_feeds' => $validated['daily_feeds'],
                'available_feeds' => $validated['available_feeds'],
                'total_feeds_consumed' => ($flockTotals['total_feeds_consumed'] - $currentEntryTotals['total_feeds_consumed']) + $validated['daily_feeds'],
                'daily_mortality' => $validated['daily_mortality'],
                'sick_bay' => $validated['sick_bay'],
                'total_mortality' => ($flockTotals['total_mortality'] - $currentEntryTotals['total_mortality']) + $validated['daily_mortality'],
                'current_birds' => $newCount,
                'daily_egg_production' => $flockTotals['daily_egg_production'],
                'daily_sold_egg' => $validated['daily_sold_egg'],
                'total_sold_egg' => ($flockTotals['total_sold_egg'] - $currentEntryTotals['total_sold_egg']) + $validated['daily_sold_egg'],
                'broken_egg' => $flockTotals['broken_egg'],
                'outstanding_egg' => $validated['outstanding_egg'],
                'total_egg_in_farm' => $flockTotals['total_egg_in_farm'],
                'drugs' => $validated['drugs'],
                'reorder_feeds' => $validated['reorder_feeds'],
            ]);

            $flock->update(['current_bird_count' => $newCount]);

            Log::info('Daily entry updated', [
                'entry_id' => $entry->id,
                'week_id' => $weekId,
                'new_bird_count' => $newCount,
            ]);

            return response()->json([
                'id' => $entry->id,
                'day_number' => "Day $entry->day_number",
                'daily_feeds' => $entry->daily_feeds,
                'daily_mortality' => $entry->daily_mortality,
                'current_birds' => $entry->current_birds,
                'daily_egg_production' => $entry->daily_egg_production,
                'created_at' => $entry->created_at->format('Y-m-d'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in DailyEntryController::update: ' . $e->getMessage(), [
                'week_id' => $weekId,
                'entry_id' => $id,
                'errors' => $e->errors(),
            ]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in DailyEntryController::update: ' . $e->getMessage(), [
                'week_id' => $weekId,
                'entry_id' => $id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Failed to update entry: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($weekId, $entryId)
    {
        try {
            Log::info('DailyEntryController::destroy called', [
                'week_id' => $weekId,
                'entry_id' => $entryId,
            ]);

            $entry = DailyEntry::where('week_entry_id', $weekId)->findOrFail($entryId);
            $week = WeekEntry::findOrFail($weekId);
            $flock = $week->flock;

            $entry->delete();

            $lastEntry = DailyEntry::where('week_entry_id', $weekId)->orderBy('day_number', 'desc')->first();
            $newCount = $lastEntry ? $lastEntry->current_birds : $flock->current_bird_count;
            $flock->update(['current_bird_count' => $newCount]);

            Log::info('Daily entry deleted', [
                'entry_id' => $entryId,
                'week_id' => $weekId,
                'new_bird_count' => $newCount,
            ]);

            return response()->json(['message' => 'Daily entry deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error in DailyEntryController::destroy: ' . $e->getMessage(), [
                'entry_id' => $entryId,
                'week_id' => $weekId,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Failed to delete daily entry: ' . $e->getMessage()], 500);
        }
    }
}