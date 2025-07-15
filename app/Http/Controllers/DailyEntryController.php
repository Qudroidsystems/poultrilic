<?php

namespace App\Http\Controllers;

use App\Models\DailyEntry;
use App\Models\WeekEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DailyEntryController extends Controller
{

    function __construct()
    {
         $this->middleware('permission:View daily-entry|Create daily-entry|Update daily-entry|Delete daily-entry', ['only' => ['index','store']]);
         $this->middleware('permission:Create daily-entry', ['only' => ['create','store']]);
         $this->middleware('permission:Update daily-entry', ['only' => ['edit','update']]);
         $this->middleware('permission:Delete daily-entry', ['only' => ['destroy']]);
    }


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
            // $this->authorize('create daily-entry');

            Log::info('Store daily entry request', [
                'week_id' => $weekId,
                'request_data' => $request->all(),
            ]);

            $week = WeekEntry::findOrFail($weekId);
            $flock = $week->flock;

            $validated = $request->validate([
                'day_number' => 'required|integer|between:1,7|unique:daily_entries,day_number,NULL,id,week_entry_id,' . $weekId,
                'daily_feeds' => 'required|numeric|min:0',
                'available_feeds' => 'required|numeric|min:0',
                'daily_mortality' => 'required|integer|min:0',
                'sick_bay' => 'required|integer|min:0',
                'daily_egg_production' => 'required|integer|min:0',
                'daily_sold_egg' => 'required|integer|min:0',
                'broken_egg' => 'required|integer|min:0',
                'drugs' => 'nullable|string',
                'reorder_feeds' => 'nullable|numeric|min:0',
            ]);

            $previousEntry = DailyEntry::where('week_entry_id', $weekId)
                ->orderByDesc('day_number')
                ->first();

            $lastBirdCount = $previousEntry ? $previousEntry->current_birds : $flock->current_bird_count;
            $currentBirds = $lastBirdCount - $validated['daily_mortality'];

            $totalFeedsConsumed = DailyEntry::where('week_entry_id', $weekId)
                ->where('day_number', '<=', $validated['day_number'])
                ->sum('daily_feeds') + $validated['daily_feeds'];

            $totalMortality = DailyEntry::where('week_entry_id', $weekId)
                ->where('day_number', '<=', $validated['day_number'])
                ->sum('daily_mortality') + $validated['daily_mortality'];

            $totalSoldEgg = DailyEntry::where('week_entry_id', $weekId)
                ->where('day_number', '<=', $validated['day_number'])
                ->sum('daily_sold_egg') + $validated['daily_sold_egg'];

            $totalEggInFarm = DailyEntry::where('week_entry_id', $weekId)
                ->where('day_number', '<=', $validated['day_number'])
                ->sum(\DB::raw('daily_egg_production - daily_sold_egg - broken_egg')) 
                + ($validated['daily_egg_production'] - $validated['daily_sold_egg'] - $validated['broken_egg']);

            $previousTotalEggInFarm = $previousEntry ? $previousEntry->total_egg_in_farm : 0;
            $outstandingEgg = $previousTotalEggInFarm + $validated['daily_egg_production'] 
                - $validated['daily_sold_egg'] - $validated['broken_egg'];

            $entry = DailyEntry::create([
                'week_entry_id' => $weekId,
                'day_number' => $validated['day_number'],
                'daily_feeds' => $validated['daily_feeds'],
                'available_feeds' => $validated['available_feeds'],
                'total_feeds_consumed' => $totalFeedsConsumed,
                'daily_mortality' => $validated['daily_mortality'],
                'sick_bay' => $validated['sick_bay'],
                'total_mortality' => $totalMortality,
                'current_birds' => $currentBirds,
                'daily_egg_production' => $validated['daily_egg_production'],
                'daily_sold_egg' => $validated['daily_sold_egg'],
                'total_sold_egg' => $totalSoldEgg,
                'broken_egg' => $validated['broken_egg'],
                'outstanding_egg' => max(0, $outstandingEgg),
                'total_egg_in_farm' => max(0, $totalEggInFarm),
                'drugs' => $validated['drugs'],
                'reorder_feeds' => $validated['reorder_feeds'],
            ]);

            $flock->update(['current_bird_count' => $currentBirds]);

            Log::info('Daily entry created', [
                'entry_id' => $entry->id,
                'week_id' => $weekId,
            ]);

            return response()->json([
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
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error storing daily entry: ' . $e->getMessage(), [
                'week_id' => $weekId,
                'errors' => $e->errors(),
            ]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::error('Authorization error storing daily entry: ' . $e->getMessage(), [
                'week_id' => $weekId,
            ]);
            return response()->json([
                'message' => 'You are not authorized to create a daily entry',
            ], 403);
        } catch (\Exception $e) {
            Log::error('Error storing daily entry: ' . $e->getMessage(), [
                'week_id' => $weekId,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Failed to store daily entry: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($weekId, $id)
    {
        try {
            // $this->authorize('view daily-entry');
            $entry = DailyEntry::where('week_entry_id', $weekId)->findOrFail($id);
            return response()->json([
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
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::error('Authorization error viewing daily entry: ' . $e->getMessage(), [
                'week_id' => $weekId,
                'entry_id' => $id,
            ]);
            return response()->json([
                'message' => 'You are not authorized to view this daily entry',
            ], 403);
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
            $this->authorize('update daily-entry');

            Log::info('Update daily entry request', [
                'week_id' => $weekId,
                'entry_id' => $id,
                'request_data' => $request->all(),
            ]);

            $week = WeekEntry::findOrFail($weekId);
            $flock = $week->flock;

            $validated = $request->validate([
                'day_number' => 'required|integer|between:1,7|unique:daily_entries,day_number,' . $id . ',id,week_entry_id,' . $weekId,
                'daily_feeds' => 'required|numeric|min:0',
                'available_feeds' => 'required|numeric|min:0',
                'daily_mortality' => 'required|integer|min:0',
                'sick_bay' => 'required|integer|min:0',
                'daily_egg_production' => 'required|integer|min:0',
                'daily_sold_egg' => 'required|integer|min:0',
                'broken_egg' => 'required|integer|min:0',
                'drugs' => 'nullable|string',
                'reorder_feeds' => 'nullable|numeric|min:0',
            ]);

            $entry = DailyEntry::where('week_entry_id', $weekId)->findOrFail($id);

            $previousEntry = DailyEntry::where('week_entry_id', $weekId)
                ->where('day_number', '<', $validated['day_number'])
                ->orderByDesc('day_number')
                ->first();

            $lastBirdCount = $previousEntry ? $previousEntry->current_birds : $flock->current_bird_count;
            $currentBirds = $lastBirdCount - $validated['daily_mortality'];

            $totalFeedsConsumed = DailyEntry::where('week_entry_id', $weekId)
                ->where('day_number', '<=', $validated['day_number'])
                ->where('id', '!=', $id)
                ->sum('daily_feeds') + $validated['daily_feeds'];

            $totalMortality = DailyEntry::where('week_entry_id', $weekId)
                ->where('day_number', '<=', $validated['day_number'])
                ->where('id', '!=', $id)
                ->sum('daily_mortality') + $validated['daily_mortality'];

            $totalSoldEgg = DailyEntry::where('week_entry_id', $weekId)
                ->where('day_number', '<=', $validated['day_number'])
                ->where('id', '!=', $id)
                ->sum('daily_sold_egg') + $validated['daily_sold_egg'];

            $totalEggInFarm = DailyEntry::where('week_entry_id', $weekId)
                ->where('day_number', '<=', $validated['day_number'])
                ->where('id', '!=', $id)
                ->sum(\DB::raw('daily_egg_production - daily_sold_egg - broken_egg')) 
                + ($validated['daily_egg_production'] - $validated['daily_sold_egg'] - $validated['broken_egg']);

            $previousTotalEggInFarm = $previousEntry ? $previousEntry->total_egg_in_farm : 0;
            $outstandingEgg = $previousTotalEggInFarm + $validated['daily_egg_production'] 
                - $validated['daily_sold_egg'] - $validated['broken_egg'];

            $entry->update([
                'day_number' => $validated['day_number'],
                'daily_feeds' => $validated['daily_feeds'],
                'available_feeds' => $validated['available_feeds'],
                'total_feeds_consumed' => $totalFeedsConsumed,
                'daily_mortality' => $validated['daily_mortality'],
                'sick_bay' => $validated['sick_bay'],
                'total_mortality' => $totalMortality,
                'current_birds' => $currentBirds,
                'daily_egg_production' => $validated['daily_egg_production'],
                'daily_sold_egg' => $validated['daily_sold_egg'],
                'total_sold_egg' => $totalSoldEgg,
                'broken_egg' => $validated['broken_egg'],
                'outstanding_egg' => max(0, $outstandingEgg),
                'total_egg_in_farm' => max(0, $totalEggInFarm),
                'drugs' => $validated['drugs'],
                'reorder_feeds' => $validated['reorder_feeds'],
            ]);

            $subsequentEntries = DailyEntry::where('week_entry_id', $weekId)
                ->where('day_number', '>', $validated['day_number'])
                ->orderBy('day_number')
                ->get();

            $previousBirdCount = $currentBirds;
            $previousTotalEggInFarm = $totalEggInFarm;

            foreach ($subsequentEntries as $subsequentEntry) {
                $newCurrentBirds = $previousBirdCount - $subsequentEntry->daily_mortality;
                $newTotalFeedsConsumed = DailyEntry::where('week_entry_id', $weekId)
                    ->where('day_number', '<=', $subsequentEntry->day_number)
                    ->where('id', '!=', $subsequentEntry->id)
                    ->sum('daily_feeds') + $subsequentEntry->daily_feeds;
                $newTotalMortality = DailyEntry::where('week_entry_id', $weekId)
                    ->where('day_number', '<=', $subsequentEntry->day_number)
                    ->where('id', '!=', $subsequentEntry->id)
                    ->sum('daily_mortality') + $subsequentEntry->daily_mortality;
                $newTotalSoldEgg = DailyEntry::where('week_entry_id', $weekId)
                    ->where('day_number', '<=', $subsequentEntry->day_number)
                    ->where('id', '!=', $subsequentEntry->id)
                    ->sum('daily_sold_egg') + $subsequentEntry->daily_sold_egg;
                $newTotalEggInFarm = DailyEntry::where('week_entry_id', $weekId)
                    ->where('day_number', '<=', $subsequentEntry->day_number)
                    ->where('id', '!=', $subsequentEntry->id)
                    ->sum(\DB::raw('daily_egg_production - daily_sold_egg - broken_egg')) 
                    + ($subsequentEntry->daily_egg_production - $subsequentEntry->daily_sold_egg - $subsequentEntry->broken_egg);
                $newOutstandingEgg = $previousTotalEggInFarm + $subsequentEntry->daily_egg_production 
                    - $subsequentEntry->daily_sold_egg - $subsequentEntry->broken_egg;

                $subsequentEntry->update([
                    'total_feeds_consumed' => $newTotalFeedsConsumed,
                    'total_mortality' => $newTotalMortality,
                    'current_birds' => $newCurrentBirds,
                    'total_sold_egg' => $newTotalSoldEgg,
                    'outstanding_egg' => max(0, $newOutstandingEgg),
                    'total_egg_in_farm' => max(0, $newTotalEggInFarm),
                ]);

                $previousBirdCount = $newCurrentBirds;
                $previousTotalEggInFarm = $newTotalEggInFarm;
            }

            $flock->update(['current_bird_count' => $currentBirds]);

            Log::info('Daily entry updated', [
                'entry_id' => $entry->id,
                'week_id' => $weekId,
            ]);

            return response()->json([
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
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error updating daily entry: ' . $e->getMessage(), [
                'week_id' => $weekId,
                'entry_id' => $id,
                'errors' => $e->errors(),
            ]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::error('Authorization error updating daily entry: ' . $e->getMessage(), [
                'week_id' => $weekId,
                'entry_id' => $id,
            ]);
            return response()->json([
                'message' => 'You are not authorized to update daily entries',
            ], 403);
        } catch (\Exception $e) {
            Log::error('Error updating daily entry: ' . $e->getMessage(), [
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
            $this->authorize('delete daily-entry');

            $entry = DailyEntry::where('week_entry_id', $weekId)->findOrFail($entryId);
            $week = WeekEntry::findOrFail($weekId);
            $flock = $week->flock;

            $entry->delete();

            $subsequentEntries = DailyEntry::where('week_entry_id', $weekId)
                ->where('day_number', '>', $entry->day_number)
                ->orderBy('day_number')
                ->get();

            $previousEntry = DailyEntry::where('week_entry_id', $weekId)
                ->where('day_number', '<', $entry->day_number)
                ->orderByDesc('day_number')
                ->first();

            $previousBirdCount = $previousEntry ? $previousEntry->current_birds : $flock->current_bird_count;
            $previousTotalEggInFarm = $previousEntry ? $previousEntry->total_egg_in_farm : 0;

            foreach ($subsequentEntries as $subsequentEntry) {
                $newCurrentBirds = $previousBirdCount - $subsequentEntry->daily_mortality;
                $newTotalFeedsConsumed = DailyEntry::where('week_entry_id', $weekId)
                    ->where('day_number', '<=', $subsequentEntry->day_number)
                    ->where('id', '!=', $subsequentEntry->id)
                    ->sum('daily_feeds') + $subsequentEntry->daily_feeds;
                $newTotalMortality = DailyEntry::where('week_entry_id', $weekId)
                    ->where('day_number', '<=', $subsequentEntry->day_number)
                    ->where('id', '!=', $subsequentEntry->id)
                    ->sum('daily_mortality') + $subsequentEntry->daily_mortality;
                $newTotalSoldEgg = DailyEntry::where('week_entry_id', $weekId)
                    ->where('day_number', '<=', $subsequentEntry->day_number)
                    ->where('id', '!=', $subsequentEntry->id)
                    ->sum('daily_sold_egg') + $subsequentEntry->daily_sold_egg;
                $newTotalEggInFarm = DailyEntry::where('week_entry_id', $weekId)
                    ->where('day_number', '<=', $subsequentEntry->day_number)
                    ->where('id', '!=', $subsequentEntry->id)
                    ->sum(\DB::raw('daily_egg_production - daily_sold_egg - broken_egg')) 
                    + ($subsequentEntry->daily_egg_production - $subsequentEntry->daily_sold_egg - $subsequentEntry->broken_egg);
                $newOutstandingEgg = $previousTotalEggInFarm + $subsequentEntry->daily_egg_production 
                    - $subsequentEntry->daily_sold_egg - $subsequentEntry->broken_egg;

                $subsequentEntry->update([
                    'total_feeds_consumed' => $newTotalFeedsConsumed,
                    'total_mortality' => $newTotalMortality,
                    'current_birds' => $newCurrentBirds,
                    'total_sold_egg' => $newTotalSoldEgg,
                    'outstanding_egg' => max(0, $newOutstandingEgg),
                    'total_egg_in_farm' => max(0, $newTotalEggInFarm),
                ]);

                $previousBirdCount = $newCurrentBirds;
                $previousTotalEggInFarm = $newTotalEggInFarm;
            }

            $lastEntry = DailyEntry::where('week_entry_id', $weekId)
                ->orderBy('day_number', 'desc')
                ->first();
            $newCount = $lastEntry ? $lastEntry->current_birds : $flock->initial_bird_count;
            $flock->update(['current_bird_count' => $newCount]);

            Log::info('Daily entry deleted', [
                'entry_id' => $entryId,
                'week_id' => $weekId,
            ]);

            return response()->json(['message' => 'Daily entry deleted successfully']);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            Log::error('Authorization error deleting daily entry: ' . $e->getMessage(), [
                'entry_id' => $entryId,
                'week_id' => $weekId,
            ]);
            return response()->json([
                'message' => 'You are not authorized to delete daily entries',
            ], 403);
        } catch (\Exception $e) {
            Log::error('Error deleting daily entry: ' . $e->getMessage(), [
                'entry_id' => $entryId,
                'week_id' => $weekId,
            ]);
            return response()->json(['error' => 'Failed to delete daily entry: ' . $e->getMessage()], 500);
        }
    }
}