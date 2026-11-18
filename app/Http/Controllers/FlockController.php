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
public function index(Request $request, \App\Services\PoultryMetrics $metrics)
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status', 'active');

        $flocks = Flock::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('breed', 'like', "%{$search}%")->orWhere('house', 'like', "%{$search}%")))
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")->orderBy('name')
            ->paginate(25)->withQueryString();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'flocks' => $flocks->map(fn ($f) => $this->present($f))->all(),
                'total' => $flocks->total(),
            ]);
        }

        $ids = $flocks->pluck('id')->all();
        $last = DailyEntry::whereIn('flock_id', $ids)->groupBy('flock_id')->selectRaw('flock_id, MAX(entry_date) as d')->pluck('d', 'flock_id');
        $deaths = DailyEntry::whereIn('flock_id', $ids)->groupBy('flock_id')->selectRaw('flock_id, SUM(daily_mortality) as d')->pluck('d', 'flock_id');

        return view('flocks.flocks.index', [
            'pagetitle' => 'Flocks',
            'flocks' => $flocks,
            'last' => $last,
            'deaths' => $deaths,
            'status' => $status,
            'search' => $search,
            'counts' => Flock::selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status'),
            'birdsNow' => (int) Flock::active()->sum('current_bird_count'),
        ]);
    }

    private function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'breed' => 'nullable|string|max:255',
            'flock_type' => 'nullable|in:' . implode(',', array_keys(Flock::TYPES)),
            'house' => 'nullable|string|max:255',
            'initial_bird_count' => 'required|integer|min:0',
            'current_bird_count' => 'nullable|integer|min:0',
            'opening_egg_stock' => 'nullable|integer|min:0',
            'date_of_arrival' => 'nullable|date',
            'age_in_weeks' => 'nullable|integer|min:0|max:150',
            'status' => 'required|in:active,inactive,sold,ended',
            'notes' => 'nullable|string|max:2000',
        ];
    }

    private function present(Flock $flock): array
    {
        return [
            'id' => $flock->id,
            'name' => $flock->name,
            'breed' => $flock->breed,
            'flock_type' => $flock->flock_type,
            'house' => $flock->house,
            'initial_bird_count' => $flock->initial_bird_count,
            'current_bird_count' => $flock->current_bird_count,
            'date_of_arrival' => $flock->date_of_arrival?->toDateString(),
            'age_in_weeks' => $flock->age_in_weeks,
            'status' => $flock->status,
            'status_formatted' => ucfirst($flock->status),
            'created_at' => $flock->created_at?->toIso8601String(),
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules() + ['name' => 'nullable|string|max:255']);
        $data['name'] = $data['name'] ?? null;
        $data['current_bird_count'] = $data['current_bird_count'] ?? $data['initial_bird_count'];
        $data['opening_egg_stock'] = $data['opening_egg_stock'] ?? 0;
        $flock = Flock::create($data);
        if (! $flock->name) {
            $flock->update(['name' => 'Flock ' . $flock->id]);
        }

        if ($request->wantsJson()) {
            return response()->json($this->present($flock), 201);
        }

        return redirect()->route('poultry.flocks.show', $flock)->with('success', "{$flock->display_name} added. Record its first day from Daily records.");
    }

    public function update(Request $request, Flock $flock)
    {
        $data = $request->validate($this->rules() + ['name' => 'nullable|string|max:255']);
        // Birds alive come from the daily records; a typed current count only applies before any are kept.
        if ($flock->dailyEntries()->exists()) {
            unset($data['current_bird_count']);
        }
        $flock->update(array_filter($data, fn ($v) => $v !== null) + ['notes' => $data['notes'] ?? $flock->notes]);

        if ($request->wantsJson()) {
            return response()->json($this->present($flock->fresh()));
        }

        return back()->with('success', 'Flock saved.');
    }

    public function destroy(Request $request, Flock $flock)
    {
        $name = $flock->display_name;
        $flock->delete();
        if ($request->wantsJson()) {
            return response()->json(['message' => 'Flock deleted successfully']);
        }

        return redirect()->route('flocks.index')->with('success', "{$name} and its records were deleted.");
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
        'total_egg_production' => DailyEntry::whereIn('week_entry_id', $weekEntries->pluck('id'))->sum('eggs_produced'),
        'total_mortality' => DailyEntry::whereIn('week_entry_id', $weekEntries->pluck('id'))->sum('daily_mortality'),
        'total_feeds_consumed' => DailyEntry::whereIn('week_entry_id', $weekEntries->pluck('id'))->sum('total_feeds_consumed'),
        'avg_daily_egg_production' => DailyEntry::whereIn('week_entry_id', $weekEntries->pluck('id'))->avg('eggs_produced') ?? 0,
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
