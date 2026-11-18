<?php

namespace App\Http\Controllers;

use App\Models\DailyEntry;
use App\Models\Flock;
use App\Models\ReportDefinition;
use App\Services\FeedStockService;
use App\Services\FlockLedger;
use App\Services\FlockMerger;
use App\Services\Import\FlockSheetImporter;
use App\Services\Import\SheetTools;
use App\Services\PoultryMetrics;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PoultryController extends Controller
{
    public function __construct(private PoultryMetrics $metrics)
    {
        $this->middleware('permission:View flock', ['only' => ['overview', 'show', 'export']]);
        $this->middleware('permission:Update flock', ['only' => ['recalculate']]);
        $this->middleware('permission:Merge flock', ['only' => ['mergeForm', 'merge']]);
    }

    /**
     * Period from the request. Relative periods end on the latest record (not today), so a farm whose records
     * are a few days behind still sees its last 30 days instead of an empty page.
     */
    public static function period(Request $request, string $default = 'last_30'): array
    {
        $period = $request->input('period', $default);
        if (! array_key_exists($period, ReportDefinition::PERIODS)) {
            $period = $default;
        }
        $latest = DailyEntry::max('entry_date');
        $anchor = $latest ? min(Carbon::parse($latest), now())->toDateString() : now()->toDateString();
        [$from, $to] = ReportDefinition::periodRange($period, $request->input('from'), $request->input('to'), in_array($period, ['last_7', 'last_30', 'last_90', 'last_12m']) ? $anchor : null);

        return [$period, $from, $to, $anchor];
    }

    /** Farm overview: the page a manager opens every morning. */
    public function overview(Request $request)
    {
        [$period, $from, $to, $anchor] = self::period($request);
        $flockIds = array_filter(array_map('intval', (array) $request->input('flocks', [])));
        $flockIds = $flockIds ?: null;

        $summary = $this->metrics->summary($flockIds, $from, $to);
        $weekly = $this->metrics->series($flockIds, $from, $to, ($from && Carbon::parse($from)->diffInDays($to ?? now()) <= 45) ? 'day' : 'week');
        $henDay = $this->metrics->byFlockOverTime('hen_day_pct', $flockIds, $from, $to, 'week');
        $perBag = $this->metrics->byFlockOverTime('eggs_per_bag', $flockIds, $from, $to, 'month');
        $table = $this->metrics->flockTable($flockIds, false);
        $observations = $this->metrics->observations($flockIds, $from, $to);
        $feed = app(FeedStockService::class)->snapshot();

        $active = Flock::active()->when($flockIds, fn ($q) => $q->whereIn('id', $flockIds))->get();
        $birdsNow = (int) $active->sum('current_bird_count');
        $pinned = ReportDefinition::where('pinned', true)->orderBy('name')->get();

        return view('poultry.overview', [
            'pagetitle' => 'Farm overview',
            'period' => $period, 'from' => $from, 'to' => $to, 'anchor' => $anchor,
            'flockIds' => $flockIds ?? [], 'allFlocks' => Flock::orderByRaw("CASE WHEN status='active' THEN 0 ELSE 1 END")->orderBy('name')->get(),
            'summary' => $summary, 'series' => $weekly, 'henDay' => $henDay, 'perBag' => $perBag,
            'table' => $table, 'observations' => $observations, 'feed' => $feed,
            'birdsNow' => $birdsNow, 'activeCount' => $active->count(), 'pinned' => $pinned,
        ]);
    }

    public function show(Request $request, Flock $flock)
    {
        [$period, $from, $to] = self::period($request, 'all');
        $first = DailyEntry::forFlock($flock->id)->min('entry_date');
        $last = DailyEntry::forFlock($flock->id)->max('entry_date');

        return view('poultry.flock', [
            'pagetitle' => $flock->display_name,
            'flock' => $flock->load('mergedInto', 'mergedFrom'),
            'period' => $period, 'from' => $from, 'to' => $to,
            'first' => $first, 'last' => $last,
            'life' => $this->metrics->summary([$flock->id]),
            'summary' => $this->metrics->summary([$flock->id], $from, $to),
            'weekly' => $this->metrics->series([$flock->id], $from, $to, 'week'),
            'monthly' => $this->metrics->series([$flock->id], $from, $to, 'month'),
            'issues' => FlockLedger::issues($flock),
            'recent' => DailyEntry::forFlock($flock->id)->latestFirst()->with('weekEntry')->limit(14)->get(),
            'weeks' => $flock->weekEntries()->orderBy('id')->get(),
        ]);
    }

    public function recalculate(Flock $flock)
    {
        FlockLedger::rebuild($flock);

        return back()->with('success', "{$flock->display_name}: birds alive, deaths, feed and eggs in farm recalculated from the daily records.");
    }

    public function mergeForm()
    {
        return view('poultry.merge', [
            'pagetitle' => 'Combine flocks',
            'flocks' => Flock::orderByRaw("CASE WHEN status='active' THEN 0 ELSE 1 END")->orderBy('name')->get()
                ->map(fn ($f) => $f->setAttribute('last_record', DailyEntry::forFlock($f->id)->max('entry_date'))),
        ]);
    }

    public function merge(Request $request, FlockMerger $merger)
    {
        $data = $request->validate([
            'sources' => 'required|array|min:1',
            'sources.*' => 'integer|exists:flocks,id',
            'date' => 'required|date|before_or_equal:today',
            'target' => 'required|in:new,existing',
            'target_id' => 'required_if:target,existing|nullable|integer|exists:flocks,id',
            'name' => 'nullable|string|max:255',
        ]);

        $target = $merger->merge(
            array_map('intval', $data['sources']),
            $data['date'],
            $data['target'] === 'existing' ? (int) $data['target_id'] : null,
            $data['name'] ?? null,
        );

        return redirect()->route('poultry.flocks.show', $target)->with('success', 'Flocks combined. The earlier flocks are closed and keep their records.');
    }

    /** Every record for the flock, in the import template's layout (edit in Excel and import back). */
    public function export(Flock $flock)
    {
        $rows = DailyEntry::forFlock($flock->id)->oldestFirst()->with('weekEntry')->get()->map(fn (DailyEntry $e) => [
            $flock->name, $e->entry_date, (int) filter_var($e->weekEntry?->week_name, FILTER_SANITIZE_NUMBER_INT) ?: null, $e->day_number,
            $e->daily_mortality, $e->birds_stolen, $e->birds_culled, $e->current_birds,
            (float) $e->daily_feeds, (float) $e->available_feeds,
            $e->eggs_produced, $e->eggs_sold, $e->broken_egg, $e->bad_eggs, $e->egg_stock,
            $e->hen_day, $e->drugs, $e->avg_weight_g !== null ? (float) $e->avg_weight_g : null, $e->notes,
        ])->all();

        $headers = array_merge(ImportController::poultryHeaders(), [['Hen-day %', 'Worked out by Poultrilic (ignored on import)']]);
        // Put the computed columns where they read naturally.
        $headers = [
            $headers[0], $headers[1], $headers[2], $headers[3], $headers[4], $headers[5], $headers[6],
            ['Birds alive (calculated)', 'From the daily losses; ignored on import'],
            $headers[8], $headers[9], $headers[10], $headers[11], $headers[12], $headers[13],
            ['Eggs in farm', 'Running egg stock'], ['Hen-day %', 'Eggs laid / birds alive'],
            $headers[14], $headers[15], $headers[16],
        ];
        $book = SheetTools::workbook('Daily records', $headers, $rows);

        return SheetTools::download($book, str($flock->display_name)->slug() . '-records-' . now()->format('Y-m-d') . '.xlsx');
    }
}
