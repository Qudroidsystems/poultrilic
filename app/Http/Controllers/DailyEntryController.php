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
    /**
     * Parse egg string (e.g., "11 Cr 12PC") to total pieces.
     *
     * @param  string|null  $eggString
     * @return int
     */
    private function parseEggString($eggString)
    {
        if (!$eggString || !is_string($eggString)) {
            return 0;
        }
        $match = preg_match('/(\d+)\s*Cr\s*(\d+)PC/', $eggString, $matches);
        return $match ? (int)$matches[1] * 30 + (int)$matches[2] : 0;
    }

    /**
     * Format total pieces to egg string (e.g., "11 Cr 12PC").
     *
     * @param  int  $totalPieces
     * @return string
     */
    private function formatEggString($totalPieces)
    {
        $totalPieces = max(0, (int)$totalPieces); // Ensure non-negative
        $crates = floor($totalPieces / 30);
        $pieces = $totalPieces % 30;
        return "{$crates} Cr {$pieces}PC";
    }

    /**
     * Display a listing of the daily entries for a given week.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $weekId
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function index(Request $request, $weekId)
    {
        try {
            $pagetitle = "Daily Entry Management";
            $week = WeekEntry::with('dailyEntries')->findOrFail($weekId);
            $flock = $week->flock;

            if (!$flock) {
                Log::error('Flock not found for week entry', ['week_id' => $weekId]);
                return response()->json(['message' => 'Flock not found'], 404);
            }

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
                'daily_egg_production' => $dailyEntries->map(fn($entry) => $this->parseEggString($entry->daily_egg_production))->toArray(),
            ];

            $totalEggInFarm = $week->dailyEntries()->get()->sum(fn($entry) => $this->parseEggString($entry->outstanding_egg));

            if ($request->ajax()) {
                return response()->json([
                    'dailyEntries' => $dailyEntries->map(function ($entry) {
                        return [
                            'id' => $entry->id,
                            'day_number' => "Day $entry->day_number",
                            'daily_feeds' => number_format($entry->daily_feeds, 2),
                            'available_feeds' => number_format($entry->available_feeds, 2),
                            'daily_mortality' => $entry->daily_mortality,
                            'sick_bay' => $entry->sick_bay,
                            'current_birds' => $entry->current_birds,
                            'daily_egg_production' => $entry->daily_egg_production ?: '0 Cr 0PC',
                            'daily_sold_egg' => $entry->daily_sold_egg ?: '0 Cr 0PC',
                            'broken_egg' => $this->formatEggString($entry->broken_egg),
                            'outstanding_egg' => $entry->outstanding_egg ?: '0 Cr 0PC',
                            'total_egg_in_farm' => $entry->total_egg_in_farm ?: '0 Cr 0PC',
                            'drugs' => $entry->drugs,
                            'reorder_feeds' => $entry->reorder_feeds !== null ? number_format($entry->reorder_feeds, 2) : null,
                            'created_at' => $entry->created_at->format('Y-m-d'),
                        ];
                    })->toArray(),
                    'pagination' => (string) $dailyEntries->links(),
                    'total' => $dailyEntries->total(),
                    'chartData' => $chartData,
                    'total_egg_in_farm' => $this->formatEggString($totalEggInFarm),
                ]);
            }

            return view('flocks.daily.index', compact('week', 'flock', 'dailyEntries', 'chartData', 'pagetitle'));
        } catch (\Exception $e) {
            Log::error('Error in daily index: ' . $e->getMessage(), [
                'week_id' => $weekId,
                'request' => $request->all(),
                'stack_trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Show the form for creating a new daily entry.
     *
     * @param  int  $weekId
     * @return \Illuminate\View\View
     */
    public function create($weekId)
    {
        try {
            $week = WeekEntry::findOrFail($weekId);
            return view('daily.create', compact('week'));
        } catch (\Exception $e) {
            Log::error('Error loading create form: ' . $e->getMessage(), ['week_id' => $weekId]);
            return response()->json(['message' => 'Failed to load create form'], 500);
        }
    }

    /**
     * Store a newly created daily entry in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $weekId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $weekId)
    {
        try {
            $weekEntry = WeekEntry::findOrFail($weekId);
            $flock = $weekEntry->flock;
            if (!$flock) {
                Log::error('Flock not found for week entry', ['week_id' => $weekId]);
                return response()->json(['message' => 'Flock not found'], 404);
            }

            $validated = $request->validate([
                'day_number' => 'required|integer|min:1|max:7|unique:daily_entries,day_number,NULL,id,week_entry_id,' . $weekId,
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
                'reorder_feeds' => 'required|numeric|min:0',
            ]);

            $dailyEggProduction = $validated['daily_egg_production_crates'] * 30 + $validated['daily_egg_production_pieces'];
            $dailySoldEgg = $validated['daily_sold_egg_crates'] * 30 + $validated['daily_sold_egg_pieces'];
            $brokenEgg = $validated['broken_egg_crates'] * 30 + $validated['broken_egg_pieces'];
            $outstandingEgg = $dailyEggProduction - $dailySoldEgg - $brokenEgg;

            if ($outstandingEgg < 0) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => ['outstanding_egg' => ['The sum of sold and broken eggs cannot exceed daily egg production.']],
                ], 422);
            }

            $totalMortality = $weekEntry->dailyEntries()->sum('daily_mortality') + $validated['daily_mortality'];
            // if ($totalMortality > $flock->initial_bird_count) {
            //     return response()->json([
            //         'message' => 'Validation failed',
            //         'errors' => ['daily_mortality' => ['Total mortality cannot exceed initial bird count.']],
            //     ], 422);
            // }

            DB::beginTransaction();

            $entry = DailyEntry::create([
                'week_entry_id' => $weekId,
                'day_number' => $validated['day_number'],
                'daily_feeds' => $validated['daily_feeds'],
                'available_feeds' => $validated['available_feeds'],
                'total_feeds_consumed' => $weekEntry->dailyEntries()->sum('daily_feeds') + $validated['daily_feeds'],
                'daily_mortality' => $validated['daily_mortality'],
                'sick_bay' => $validated['sick_bay'],
                'total_mortality' => $totalMortality,
                'current_birds' => max(0, $flock->initial_bird_count - $totalMortality),
                'daily_egg_production' => $this->formatEggString($dailyEggProduction),
                'daily_sold_egg' => $this->formatEggString($dailySoldEgg),
                'total_sold_egg' => $this->formatEggString(
                    $weekEntry->dailyEntries()->get()->sum(fn($entry) => $this->parseEggString($entry->daily_sold_egg)) + $dailySoldEgg
                ),
                'broken_egg' => $brokenEgg,
                'outstanding_egg' => $this->formatEggString($outstandingEgg),
                'total_egg_in_farm' => $this->formatEggString(
                    $weekEntry->dailyEntries()->get()->sum(fn($entry) => $this->parseEggString($entry->outstanding_egg)) + $outstandingEgg
                ),
                'drugs' => $validated['drugs'],
                'reorder_feeds' => $validated['reorder_feeds'],
            ]);

            $flock->current_bird_count = max(0, $flock->initial_bird_count - $totalMortality);
            $flock->save();

            DB::commit();

            return response()->json([
                'message' => 'Daily entry created successfully',
                'data' => [
                    'id' => $entry->id,
                    'day_number' => "Day $entry->day_number",
                    'daily_feeds' => number_format($entry->daily_feeds, 2),
                    'available_feeds' => number_format($entry->available_feeds, 2),
                    'daily_mortality' => $entry->daily_mortality,
                    'sick_bay' => $entry->sick_bay,
                    'current_birds' => $entry->current_birds,
                    'daily_egg_production' => $entry->daily_egg_production,
                    'daily_sold_egg' => $entry->daily_sold_egg,
                    'broken_egg' => $this->formatEggString($entry->broken_egg),
                    'outstanding_egg' => $entry->outstanding_egg,
                    'total_egg_in_farm' => $entry->total_egg_in_farm,
                    'drugs' => $entry->drugs,
                    'reorder_feeds' => $entry->reorder_feeds !== null ? number_format($entry->reorder_feeds, 2) : null,
                    'created_at' => $entry->created_at->format('Y-m-d'),
                ],
                'total_egg_in_farm' => $entry->total_egg_in_farm,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error creating daily entry: ' . $e->getMessage(), [
                'week_id' => $weekId,
                'errors' => $e->errors(),
            ]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating daily entry: ' . $e->getMessage(), [
                'week_id' => $weekId,
                'stack_trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'An error occurred while creating the daily entry',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified daily entry.
     *
     * @param  int  $weekId
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($weekId, $id)
    {
        try {
            $entry = DailyEntry::where('week_entry_id', $weekId)->findOrFail($id);
            return response()->json([
                'id' => $entry->id,
                'day_number' => $entry->day_number,
                'daily_feeds' => number_format($entry->daily_feeds, 2),
                'available_feeds' => number_format($entry->available_feeds, 2),
                'daily_mortality' => $entry->daily_mortality,
                'sick_bay' => $entry->sick_bay,
                'current_birds' => $entry->current_birds,
                'daily_egg_production' => $entry->daily_egg_production ?: '0 Cr 0PC',
                'daily_sold_egg' => $entry->daily_sold_egg ?: '0 Cr 0PC',
                'broken_egg' => $this->formatEggString($entry->broken_egg),
                'outstanding_egg' => $entry->outstanding_egg ?: '0 Cr 0PC',
                'total_egg_in_farm' => $entry->total_egg_in_farm ?: '0 Cr 0PC',
                'drugs' => $entry->drugs,
                'reorder_feeds' => $entry->reorder_feeds !== null ? number_format($entry->reorder_feeds, 2) : null,
                'created_at' => $entry->created_at->format('Y-m-d'),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Daily entry not found: ' . $e->getMessage(), [
                'week_id' => $weekId,
                'entry_id' => $id,
            ]);
            return response()->json(['message' => 'Daily entry not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error viewing daily entry: ' . $e->getMessage(), [
                'week_id' => $weekId,
                'entry_id' => $id,
                'stack_trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Failed to retrieve daily entry',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified daily entry in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $weekId
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $weekId, $id)
    {
        try {
            Log::info('Starting update method', [
                'week_id' => $weekId,
                'entry_id' => $id,
                'request_data' => $request->all(),
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
                    'errors' => ['outstanding_egg' => ['The sum of sold and broken eggs cannot exceed daily egg production.']],
                ], 422);
            }

            $totalMortality = $weekEntry->dailyEntries()->where('id', '!=', $id)->sum('daily_mortality') + $validated['daily_mortality'];
            if ($totalMortality > $flock->initial_bird_count) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => ['daily_mortality' => ['Total mortality cannot exceed initial bird count.']],
                ], 422);
            }

            DB::beginTransaction();

            $dailyEntry->update([
                'day_number' => $validated['day_number'],
                'daily_feeds' => $validated['daily_feeds'],
                'available_feeds' => $validated['available_feeds'],
                'total_feeds_consumed' => $weekEntry->dailyEntries()->where('id', '!=', $id)->sum('daily_feeds') + $validated['daily_feeds'],
                'daily_mortality' => $validated['daily_mortality'],
                'sick_bay' => $validated['sick_bay'],
                'total_mortality' => $totalMortality,
                'current_birds' => max(0, $flock->initial_bird_count - $totalMortality),
                'daily_egg_production' => $this->formatEggString($dailyEggProduction),
                'daily_sold_egg' => $this->formatEggString($dailySoldEgg),
                'total_sold_egg' => $this->formatEggString(
                    $weekEntry->dailyEntries()->where('id', '!=', $id)->get()->sum(fn($entry) => $this->parseEggString($entry->daily_sold_egg)) + $dailySoldEgg
                ),
                'broken_egg' => $brokenEgg,
                'outstanding_egg' => $this->formatEggString($outstandingEgg),
                'total_egg_in_farm' => $this->formatEggString(
                    $weekEntry->dailyEntries()->where('id', '!=', $id)->get()->sum(fn($entry) => $this->parseEggString($entry->outstanding_egg)) + $outstandingEgg
                ),
                'drugs' => $validated['drugs'],
                'reorder_feeds' => $validated['reorder_feeds'],
            ]);

            $flock->current_bird_count = max(0, $flock->initial_bird_count - $totalMortality);
            $flock->save();

            DB::commit();

            return response()->json([
                'message' => 'Daily entry updated successfully',
                'data' => [
                    'id' => $dailyEntry->id,
                    'day_number' => "Day $dailyEntry->day_number",
                    'daily_feeds' => number_format($dailyEntry->daily_feeds, 2),
                    'available_feeds' => number_format($dailyEntry->available_feeds, 2),
                    'daily_mortality' => $dailyEntry->daily_mortality,
                    'sick_bay' => $dailyEntry->sick_bay,
                    'current_birds' => $dailyEntry->current_birds,
                    'daily_egg_production' => $dailyEntry->daily_egg_production,
                    'daily_sold_egg' => $dailyEntry->daily_sold_egg,
                    'broken_egg' => $this->formatEggString($dailyEntry->broken_egg),
                    'outstanding_egg' => $dailyEntry->outstanding_egg,
                    'total_egg_in_farm' => $dailyEntry->total_egg_in_farm,
                    'drugs' => $dailyEntry->drugs,
                    'reorder_feeds' => $dailyEntry->reorder_feeds !== null ? number_format($dailyEntry->reorder_feeds, 2) : null,
                    'created_at' => $dailyEntry->created_at->format('Y-m-d'),
                ],
                'total_egg_in_farm' => $dailyEntry->total_egg_in_farm,
            ], 200);
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
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Daily entry or week not found: ' . $e->getMessage(), [
                'week_id' => $weekId,
                'entry_id' => $id,
            ]);
            return response()->json(['message' => 'Daily entry or week not found'], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating daily entry: ' . $e->getMessage(), [
                'week_id' => $weekId,
                'entry_id' => $id,
                'stack_trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'An error occurred while updating the daily entry',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified daily entry from storage.
     *
     * @param  int  $weekId
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
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

            $totalMortality = $weekEntry->dailyEntries()->sum('daily_mortality');
            $flock->current_bird_count = max(0, $flock->initial_bird_count - $totalMortality);
            $flock->save();

            DB::commit();

            return response()->json(['message' => 'Daily entry deleted successfully'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Daily entry or week not found: ' . $e->getMessage(), [
                'week_id' => $weekId,
                'entry_id' => $id,
            ]);
            return response()->json(['message' => 'Daily entry or week not found'], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting daily entry: ' . $e->getMessage(), [
                'week_id' => $weekId,
                'entry_id' => $id,
                'stack_trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'An error occurred while deleting the daily entry',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
