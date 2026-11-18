<?php

namespace App\Http\Controllers;

use App\Models\DailyEntry;
use App\Support\Eggs;
use App\Models\WeekEntry;
use App\Models\Flock;
use App\Services\DailyEntryRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DailyEntryController extends Controller
{
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
                'daily_egg_production' => $dailyEntries->map(fn($entry) => (int) $entry->eggs_produced)->toArray(),
            ];

            $totalEggInFarm = (int) $week->dailyEntries()->sum('eggs_outstanding');

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
                            'broken_egg' => Eggs::format($entry->broken_egg),
                            'outstanding_egg' => $entry->outstanding_egg ?: '0 Cr 0PC',
                            'total_egg_in_farm' => $entry->total_egg_in_farm ?: '0 Cr 0PC',
                            'drugs' => $entry->drugs,
                            'reorder_feeds' => $entry->reorder_feeds !== null ? number_format($entry->reorder_feeds, 2) : null,
                            'created_at' => ($entry->entry_date ?? $entry->created_at)->format('Y-m-d'),
                            'entry_date' => ($entry->entry_date ?? $entry->created_at)->format('Y-m-d'),
                        ];
                    })->toArray(),
                    'pagination' => (string) $dailyEntries->links(),
                    'total' => $dailyEntries->total(),
                    'chartData' => $chartData,
                    'total_egg_in_farm' => Eggs::format($totalEggInFarm),
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
     * Validation shared by store and update. Eggs come in as crates + pieces; the new loss and bad-egg
     * fields are optional so older forms keep working.
     */
    private function rules(): array
    {
        return [
            'daily_feeds' => 'required|numeric|min:0',
            'available_feeds' => 'required|numeric|min:0',
            'daily_mortality' => 'required|integer|min:0',
            'birds_stolen' => 'nullable|integer|min:0',
            'birds_culled' => 'nullable|integer|min:0',
            'sick_bay' => 'required|integer|min:0',
            'daily_egg_production_crates' => 'required|integer|min:0',
            'daily_egg_production_pieces' => 'required|integer|min:0|max:29',
            'daily_sold_egg_crates' => 'required|integer|min:0',
            'daily_sold_egg_pieces' => 'required|integer|min:0|max:29',
            'broken_egg_crates' => 'required|integer|min:0',
            'broken_egg_pieces' => 'required|integer|min:0|max:29',
            'bad_eggs' => 'nullable|integer|min:0',
            'drugs' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'avg_weight_g' => 'nullable|numeric|min:0',
            'reported_birds_alive' => 'nullable|integer|min:0',
            'reorder_feeds' => 'nullable|numeric|min:0',
        ];
    }

    /** Validated form input as the recorder's raw inputs (egg pieces). */
    private function inputs(array $v): array
    {
        return [
            'entry_date' => $v['entry_date'] ?? null,
            'day_number' => $v['day_number'],
            'daily_feeds' => $v['daily_feeds'],
            'available_feeds' => $v['available_feeds'],
            'daily_mortality' => $v['daily_mortality'],
            'birds_stolen' => $v['birds_stolen'] ?? 0,
            'birds_culled' => $v['birds_culled'] ?? 0,
            'sick_bay' => $v['sick_bay'],
            'eggs_produced' => Eggs::toPieces($v['daily_egg_production_crates'], $v['daily_egg_production_pieces']),
            'eggs_sold' => Eggs::toPieces($v['daily_sold_egg_crates'], $v['daily_sold_egg_pieces']),
            'broken_egg' => Eggs::toPieces($v['broken_egg_crates'], $v['broken_egg_pieces']),
            'bad_eggs' => $v['bad_eggs'] ?? 0,
            'drugs' => $v['drugs'] ?? null,
            'notes' => $v['notes'] ?? null,
            'avg_weight_g' => $v['avg_weight_g'] ?? null,
            'reported_birds_alive' => $v['reported_birds_alive'] ?? null,
            'reorder_feeds' => $v['reorder_feeds'] ?? null,
            'source' => 'web',
        ];
    }

    private function present(DailyEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'day_number' => "Day $entry->day_number",
            'daily_feeds' => number_format($entry->daily_feeds, 2),
            'available_feeds' => number_format($entry->available_feeds, 2),
            'daily_mortality' => $entry->daily_mortality,
            'birds_stolen' => $entry->birds_stolen,
            'birds_culled' => $entry->birds_culled,
            'total_mortality' => $entry->total_mortality,
            'sick_bay' => $entry->sick_bay,
            'current_birds' => $entry->current_birds,
            'hen_day' => $entry->hen_day,
            'daily_egg_production' => $entry->daily_egg_production ?: '0 Cr 0PC',
            'daily_sold_egg' => $entry->daily_sold_egg ?: '0 Cr 0PC',
            'broken_egg' => Eggs::format($entry->broken_egg),
            'bad_eggs' => $entry->bad_eggs,
            'outstanding_egg' => $entry->outstanding_egg ?: '0 Cr 0PC',
            'total_egg_in_farm' => $entry->total_egg_in_farm ?: '0 Cr 0PC',
            'drugs' => $entry->drugs,
            'notes' => $entry->notes,
            'reorder_feeds' => $entry->reorder_feeds !== null ? number_format($entry->reorder_feeds, 2) : null,
            'created_at' => ($entry->entry_date ?? $entry->created_at)->format('Y-m-d'),
            'entry_date' => ($entry->entry_date ?? $entry->created_at)->format('Y-m-d'),
        ];
    }

    /**
     * Store a newly created daily entry. Running totals are recalculated for the whole flock by the ledger.
     */
    public function store(Request $request, $weekId, DailyEntryRecorder $recorder)
    {
        try {
            $weekEntry = WeekEntry::findOrFail($weekId);
            $flock = $weekEntry->flock;
            if (!$flock) {
                return response()->json(['message' => 'Flock not found'], 404);
            }

            $validated = $request->validate($this->rules() + [
                'day_number' => 'required|integer|min:1|max:7|unique:daily_entries,day_number,NULL,id,week_entry_id,' . $weekId,
                'entry_date' => ['nullable', 'date', 'before_or_equal:today',
                    Rule::unique('daily_entries', 'entry_date')->where('flock_id', $flock->id)],
            ]);

            $entry = DB::transaction(fn () => $recorder->record($flock, $this->inputs($validated), null, false, $weekEntry));

            return response()->json([
                'message' => 'Daily entry created successfully',
                'data' => $this->present($entry),
                'total_egg_in_farm' => $entry->total_egg_in_farm,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error creating daily entry: ' . $e->getMessage(), ['week_id' => $weekId, 'stack_trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'An error occurred while creating the daily entry', 'error' => $e->getMessage()], 500);
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

            return response()->json(['day_number' => $entry->day_number] + $this->present($entry));
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
     * Update the specified daily entry. Selling more eggs than were laid that day is allowed (eggs come out
     * of stock); the ledger flags it if the stock would go negative.
     */
    public function update(Request $request, $weekId, $id, DailyEntryRecorder $recorder)
    {
        try {
            $weekEntry = WeekEntry::findOrFail($weekId);
            $flock = $weekEntry->flock;
            if (!$flock) {
                return response()->json(['message' => 'Flock not found'], 404);
            }

            $dailyEntry = DailyEntry::where('week_entry_id', $weekId)->findOrFail($id);

            // Only enforce one-entry-per-flock-per-date when the date is being changed, so legacy
            // rows whose backfilled date collides can still be edited without touching the date.
            $dateRules = ['nullable', 'date', 'before_or_equal:today'];
            if ($request->filled('entry_date') && $request->input('entry_date') !== optional($dailyEntry->entry_date)->format('Y-m-d')) {
                $dateRules[] = Rule::unique('daily_entries', 'entry_date')->where('flock_id', $flock->id)->ignore($id);
            }

            $validated = $request->validate($this->rules() + [
                'day_number' => 'required|integer|between:1,7|unique:daily_entries,day_number,' . $id . ',id,week_entry_id,' . $weekId,
                'entry_date' => $dateRules,
            ]);

            $inputs = $this->inputs($validated);
            $inputs['entry_date'] = $validated['entry_date'] ?? ($dailyEntry->entry_date ?? now())->format('Y-m-d');

            $dailyEntry = DB::transaction(fn () => $recorder->record($flock, $inputs, $dailyEntry, false, $weekEntry));

            return response()->json([
                'message' => 'Daily entry updated successfully',
                'data' => $this->present($dailyEntry),
                'total_egg_in_farm' => $dailyEntry->total_egg_in_farm,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Daily entry or week not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error updating daily entry: ' . $e->getMessage(), ['week_id' => $weekId, 'entry_id' => $id]);
            return response()->json(['message' => 'An error occurred while updating the daily entry', 'error' => $e->getMessage()], 500);
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

            // Deleting recalculates the flock's running totals (see DailyEntry::boot / FlockLedger).
            DB::transaction(fn () => $dailyEntry->delete());

            return response()->json(['message' => 'Daily entry deleted successfully'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Daily entry or week not found: ' . $e->getMessage(), [
                'week_id' => $weekId,
                'entry_id' => $id,
            ]);
            return response()->json(['message' => 'Daily entry or week not found'], 404);
        } catch (\Exception $e) {
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
