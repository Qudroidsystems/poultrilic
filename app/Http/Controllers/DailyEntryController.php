<?php

namespace App\Http\Controllers;

use App\Models\DailyEntry;
use App\Models\WeekEntry;
use App\Models\Flock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DailyEntryController extends Controller
{
    public function index(Request $request, $weekId)
    {
        try {
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
                return response()->json([
                    'dailyEntries' => $dailyEntries->map(function ($entry) {
                        return [
                            'id' => $entry->id,
                            'day_number' => "Day $entry->day_number",
                            'daily_feeds' => $entry->daily_feeds,
                            'available_feeds' => $entry->available_feeds,
                            'daily_mortality' => $entry->daily_mortality,
                            'sick_bay' => $entry->sick_bay,
                            'current_birds' => $entry->current_birds,
                            'daily_egg_production' => $entry->daily_egg_production,
                            'daily_sold_egg' => $entry->daily_sold_egg,
                            'broken_egg' => $entry->broken_egg,
                            'outstanding_egg' => $entry->outstanding_egg,
                            'total_egg_in_farm' => $entry->total_egg_in_farm,
                            'drugs' => $entry->drugs,
                            'reorder_feeds' => $entry->reorder_feeds,
                            'created_at' => $entry->created_at->format('Y-m-d'),
                        ];
                    })->toArray(),
                    'pagination' => (string) $dailyEntries->links(),
                    'total' => $dailyEntries->total(),
                    'chartData' => $chartData,
                    'total_egg_in_farm' => $week->dailyEntries()->sum('outstanding_egg')
                ]);
            }

            return view('flocks.daily.index', compact('week', 'flock', 'dailyEntries', 'chartData', 'pagetitle'));
        } catch (\Exception $e) {
            Log::error('Error in daily index: ' . $e->getMessage(), [
                'week_id' => $weekId,
                'request' => $request->all(),
            ]);
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function create($weekId)
    {
        $week = WeekEntry::findOrFail($weekId);
        return view('daily.create', compact('week'));
    }

    public function store(Request $request, $weekId)
    {
        try {
            Log::info('Starting store method', ['week_id' => $weekId, 'request_data' => $request->all()]);

            $weekEntry = WeekEntry::findOrFail($weekId);
            $flock = $weekEntry->flock;
            if (!$flock) {
                Log::error('Flock not found for week entry', ['week_id' => $weekId]);
                return response()->json(['message' => 'Flock not found'], 404);
            }

            $validated = $request->validate([
                'day_number' => 'required|integer|between:1,7|unique:daily_entries,day_number,NULL,id,week_entry_id,' . $weekId,
                'daily_feeds' => 'required|numeric|min:0',
                'available_feeds' => 'required|numeric|min:0',
                'daily_mortality' => 'required|integer|min:0',
                'sick_bay' => 'required|integer|min:0',
                'daily_egg_production_crates' => 'required|integer|min:0',
                'daily_egg_production_pieces' => 'required|integer|min:0|max:29',
                'daily_sold_egg_crates' => 'required|integer|min:0',
                'daily_sold_egg_pieces' => 'required|integer|min:0|max:29',
                'broken_egg_crates' => 'required|integer|min:0',
                'broken_egg_pieces' => 'required|integer|min:0|max:29',
                'drugs' => 'nullable|string',
                'reorder_feeds' => 'nullable|numeric|min:0',
            ]);

            $dailyEggProduction = $validated['daily_egg_production_crates'] * 30 + $validated['daily_egg_production_pieces'];
            $dailySoldEgg = $validated['daily_sold_egg_crates'] * 30 + $validated['daily_sold_egg_pieces'];
            $brokenEgg = $validated['broken_egg_crates'] * 30 + $validated['broken_egg_pieces'];
            $outstandingEgg = $dailyEggProduction - $dailySoldEgg - $brokenEgg;

            if ($outstandingEgg < 0) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => ['outstanding_egg' => ['The sum of sold and broken eggs cannot exceed daily egg production.']]
                ], 422);
            }

            $totalMortality = $flock->weekEntries()->join('daily_entries', 'week_entries.id', '=', 'daily_entries.week_entry_id')
                ->sum('daily_entries.daily_mortality') + $validated['daily_mortality'];
            if ($totalMortality > $flock->initial_bird_count) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => ['daily_mortality' => ['Total mortality cannot exceed initial bird count.']]
                ], 422);
            }

            DB::beginTransaction();

            $dailyEntry = DailyEntry::create([
                'week_entry_id' => $weekId,
                'day_number' => $validated['day_number'],
                'daily_feeds' => $validated['daily_feeds'],
                'available_feeds' => $validated['available_feeds'],
                'total_feeds_consumed' => $flock->weekEntries()->join('daily_entries', 'week_entries.id', '=', 'daily_entries.week_entry_id')
                    ->sum('daily_entries.daily_feeds') + $validated['daily_feeds'],
                'daily_mortality' => $validated['daily_mortality'],
                'sick_bay' => $validated['sick_bay'],
                'total_mortality' => $totalMortality,
                'current_birds' => max(0, $flock->initial_bird_count - $totalMortality),
                'daily_egg_production' => $dailyEggProduction,
                'daily_sold_egg' => $dailySoldEgg,
                'total_sold_egg' => $flock->weekEntries()->join('daily_entries', 'week_entries.id', '=', 'daily_entries.week_entry_id')
                    ->sum('daily_entries.daily_sold_egg') + $dailySoldEgg,
                'broken_egg' => $brokenEgg,
                'outstanding_egg' => $outstandingEgg,
                'total_egg_in_farm' => $weekEntry->dailyEntries()->sum('outstanding_egg') + $outstandingEgg,
                'drugs' => $validated['drugs'],
                'reorder_feeds' => $validated['reorder_feeds'],
            ]);

            $flock->current_bird_count = max(0, $flock->initial_bird_count - $totalMortality);
            $flock->save();

            DB::commit();

            return response()->json([
                'message' => 'Daily entry created successfully',
                'data' => $dailyEntry,
                'total_egg_in_farm' => $dailyEntry->total_egg_in_farm
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error creating daily entry: ' . $e->getMessage(), [
                'week_id' => $weekId,
                'errors' => $e->errors()
            ]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating daily entry: ' . $e->getMessage(), [
                'week_id' => $weekId,
                'stack_trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'An error occurred while creating the daily entry',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($weekId, $id)
    {
        try {
            $entry = DailyEntry::where('week_entry_id', $weekId)->findOrFail($id);
            return response()->json([
                'id' => $entry->id,
                'day_number' => $entry->day_number,
                'daily_feeds' => $entry->daily_feeds,
                'available_feeds' => $entry->available_feeds,
                'daily_mortality' => $entry->daily_mortality,
                'sick_bay' => $entry->sick_bay,
                'current_birds' => $entry->current_birds,
                'daily_egg_production' => $entry->daily_egg_production,
                'daily_sold_egg' => $entry->daily_sold_egg,
                'broken_egg' => $entry->broken_egg,
                'outstanding_egg' => $entry->outstanding_egg,
                'total_egg_in_farm' => $entry->total_egg_in_farm,
                'drugs' => $entry->drugs,
                'reorder_feeds' => $entry->reorder_feeds,
                'created_at' => $entry->created_at->format('Y-m-d'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error viewing daily entry: ' . $e->getMessage(), [
                'week_id' => $weekId,
                'entry_id' => $id,
            ]);
            return response()->json([
                'message' => 'Failed to retrieve daily entry: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $weekId, $id)
    {
        try {
            Log::info('Starting update method', [
                'week_id' => $weekId,
                'entry_id' => $id,
                'request_data' => $request->all()
            ]);

            $weekEntry = WeekEntry::findOrFail($weekId);
            $flock = $weekEntry->flock;
            if (!$flock) {
                Log::error('Flock not found for week entry', ['week_id' => $weekId]);
                return response()->json(['message' => 'Flock not found'], 404);
            }

            $dailyEntry = DailyEntry::where('week_entry_id', $weekId)->findOrFail($id);

            $validated = $request->validate([
                'day_number' => 'required|integer|between:1,7|unique:daily_entries,day_number,' . $id . ',id,week_entry_id,' . $weekId,
                'daily_feeds' => 'required|numeric|min:0',
                'available_feeds' => 'required|numeric|min:0',
                'daily_mortality' => 'required|integer|min:0',
                'sick_bay' => 'required|integer|min:0',
                'daily_egg_production_crates' => 'required|integer|min:0',
                'daily_egg_production_pieces' => 'required|integer|min:0|max:29',
                'daily_sold_egg_crates' => 'required|integer|min:0',
                'daily_sold_egg_pieces' => 'required|integer|min:0|max:29',
                'broken_egg_crates' => 'required|integer|min:0',
                'broken_egg_pieces' => 'required|integer|min:0|max:29',
                'drugs' => 'nullable|string',
                'reorder_feeds' => 'nullable|numeric|min:0',
            ]);

            $dailyEggProduction = $validated['daily_egg_production_crates'] * 30 + $validated['daily_egg_production_pieces'];
            $dailySoldEgg = $validated['daily_sold_egg_crates'] * 30 + $validated['daily_sold_egg_pieces'];
            $brokenEgg = $validated['broken_egg_crates'] * 30 + $validated['broken_egg_pieces'];
            $outstandingEgg = $dailyEggProduction - $dailySoldEgg - $brokenEgg;

            if ($outstandingEgg < 0) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => ['outstanding_egg' => ['The sum of sold and broken eggs cannot exceed daily egg production.']]
                ], 422);
            }

            $totalMortality = $flock->weekEntries()->join('daily_entries', 'week_entries.id', '=', 'daily_entries.week_entry_id')
                ->where('daily_entries.id', '!=', $id)
                ->sum('daily_entries.daily_mortality') + $validated['daily_mortality'];
            if ($totalMortality > $flock->initial_bird_count) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => ['daily_mortality' => ['Total mortality cannot exceed initial bird count.']]
                ], 422);
            }

            DB::beginTransaction();

            $dailyEntry->update([
                'day_number' => $validated['day_number'],
                'daily_feeds' => $validated['daily_feeds'],
                'available_feeds' => $validated['available_feeds'],
                'total_feeds_consumed' => $flock->weekEntries()->join('daily_entries', 'week_entries.id', '=', 'daily_entries.week_entry_id')
                    ->where('daily_entries.id', '!=', $id)->sum('daily_entries.daily_feeds') + $validated['daily_feeds'],
                'daily_mortality' => $validated['daily_mortality'],
                'sick_bay' => $validated['sick_bay'],
                'total_mortality' => $totalMortality,
                'current_birds' => max(0, $flock->initial_bird_count - $totalMortality),
                'daily_egg_production' => $dailyEggProduction,
                'daily_sold_egg' => $dailySoldEgg,
                'total_sold_egg' => $flock->weekEntries()->join('daily_entries', 'week_entries.id', '=', 'daily_entries.week_entry_id')
                    ->where('daily_entries.id', '!=', $id)->sum('daily_entries.daily_sold_egg') + $dailySoldEgg,
                'broken_egg' => $brokenEgg,
                'outstanding_egg' => $outstandingEgg,
                'total_egg_in_farm' => $weekEntry->dailyEntries()->where('id', '!=', $id)->sum('outstanding_egg') + $outstandingEgg,
                'drugs' => $validated['drugs'],
                'reorder_feeds' => $validated['reorder_feeds'],
            ]);

            $flock->current_bird_count = max(0, $flock->initial_bird_count - $totalMortality);
            $flock->save();

            DB::commit();

            return response()->json([
                'message' => 'Daily entry updated successfully',
                'data' => $dailyEntry,
                'total_egg_in_farm' => $dailyEntry->total_egg_in_farm
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error updating daily entry: ' . $e->getMessage(), [
                'week_id' => $weekId,
                'entry_id' => $id,
                'errors' => $e->errors()
            ]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating daily entry: ' . $e->getMessage(), [
                'week_id' => $weekId,
                'entry_id' => $id,
                'stack_trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'An error occurred while updating the daily entry',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($weekId, $id)
    {
        try {
            Log::info('Starting destroy method', ['week_id' => $weekId, 'entry_id' => $id]);

            $weekEntry = WeekEntry::findOrFail($weekId);
            $flock = $weekEntry->flock;
            if (!$flock) {
                Log::error('Flock not found for week entry', ['week_id' => $weekId]);
                return response()->json(['message' => 'Flock not found'], 404);
            }

            $dailyEntry = DailyEntry::where('week_entry_id', $weekId)->findOrFail($id);

            DB::beginTransaction();

            $dailyEntry->delete();

            $totalMortality = $flock->weekEntries()->join('daily_entries', 'week_entries.id', '=', 'daily_entries.week_entry_id')
                ->sum('daily_entries.daily_mortality');
            $flock->current_bird_count = max(0, $flock->initial_bird_count - $totalMortality);
            $flock->save();

            DB::commit();

            return response()->json(['message' => 'Daily entry deleted successfully'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting daily entry: ' . $e->getMessage(), [
                'week_id' => $weekId,
                'entry_id' => $id,
                'stack_trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'An error occurred while deleting the daily entry',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
