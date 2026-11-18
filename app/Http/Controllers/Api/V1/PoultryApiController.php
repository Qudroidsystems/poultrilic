<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\PoultryController;
use App\Models\DailyEntry;
use App\Models\Flock;
use App\Services\DailyEntryRecorder;
use App\Services\FeedStockService;
use App\Services\FlockLedger;
use App\Services\PoultryMetrics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Poultry for the mobile app. Eggs are whole pieces; feed is bags. */
class PoultryApiController extends ApiController
{
    public function __construct(private PoultryMetrics $metrics)
    {
    }

    /** Home screen: KPIs for the period, what needs attention, active flocks. */
    public function dashboard(Request $request)
    {
        $this->authorizeTo('View flock');
        [$period, $from, $to] = PoultryController::period($request);
        $s = $this->metrics->summary(null, $from, $to);
        $feed = app(FeedStockService::class)->snapshot();

        return $this->ok([
            'period' => ['key' => $period, 'from' => $from, 'to' => $to],
            'summary' => $s,
            'birds_now' => (int) Flock::active()->sum('current_bird_count'),
            'feed' => ['tracking' => $feed['tracking'], 'on_hand' => $feed['on_hand'], 'days_remaining' => $feed['days_remaining'], 'needs_reorder' => $feed['needs_reorder']],
            'observations' => array_map(fn ($o) => ['severity' => $o[0], 'title' => $o[1], 'detail' => $o[2]], $this->metrics->observations(null, $from, $to)),
            'flocks' => array_map(fn ($row) => $this->flockRow($row['flock'], $row['recent'], $row['last'], $row['issues']), $this->metrics->flockTable(null, false)),
            'trend' => array_map(fn ($r) => ['label' => $r['label'], 'eggs_laid' => $r['eggs_laid'], 'hen_day_pct' => $r['hen_day_pct'], 'deaths' => $r['deaths']],
                $this->metrics->series(null, $from, $to, 'day')),
        ], null, ['metrics' => PoultryMetrics::METRICS]);
    }

    public function flocks(Request $request)
    {
        $this->authorizeTo('View flock');
        $flocks = Flock::query()
            ->when($request->input('status', 'active') !== 'all', fn ($q) => $q->where('status', $request->input('status', 'active')))
            ->orderBy('name')->get();

        return $this->ok($flocks->map(fn ($f) => $this->flockRow($f, null, DailyEntry::forFlock($f->id)->max('entry_date')))->all());
    }

    public function flock(Request $request, Flock $flock)
    {
        $this->authorizeTo('View flock');
        [$period, $from, $to] = PoultryController::period($request, 'last_30');

        return $this->ok($this->flockRow($flock, null, DailyEntry::forFlock($flock->id)->max('entry_date')) + [
            'lifetime' => $this->metrics->summary([$flock->id]),
            'period' => ['key' => $period, 'from' => $from, 'to' => $to],
            'summary' => $this->metrics->summary([$flock->id], $from, $to),
            'weekly' => $this->metrics->series([$flock->id], $from, $to, 'week'),
            'issues' => FlockLedger::issues($flock),
            'notes' => $flock->notes,
        ]);
    }

    public function entries(Request $request, Flock $flock)
    {
        $this->authorizeTo('View flock');
        $page = DailyEntry::forFlock($flock->id)
            ->when($request->filled('from'), fn ($q) => $q->where('entry_date', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->where('entry_date', '<=', $request->input('to')))
            ->latestFirst()->paginate(min(100, (int) $request->input('per_page', 31)));

        return $this->ok(collect($page->items())->map(fn ($e) => $this->entry($e))->all(), null, [
            'page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total(),
        ]);
    }

    /** Save a day (creates, or updates the day already recorded for that date). */
    public function saveEntry(Request $request, Flock $flock, DailyEntryRecorder $recorder)
    {
        $existing = DailyEntry::forFlock($flock->id)->where('entry_date', $request->input('entry_date'))->first();
        $this->authorizeTo($existing ? 'Update daily-entry' : 'Create daily-entry');

        $data = $request->validate([
            'entry_date' => 'required|date|before_or_equal:today',
            'daily_mortality' => 'required|integer|min:0',
            'birds_stolen' => 'nullable|integer|min:0',
            'birds_culled' => 'nullable|integer|min:0',
            'sick_bay' => 'nullable|integer|min:0',
            'daily_feeds' => 'required|numeric|min:0',
            'available_feeds' => 'nullable|numeric|min:0',
            'eggs_produced' => 'nullable|integer|min:0',
            'eggs_sold' => 'nullable|integer|min:0',
            'broken_egg' => 'nullable|integer|min:0',
            'bad_eggs' => 'nullable|integer|min:0',
            'drugs' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'avg_weight_g' => 'nullable|numeric|min:0',
            'reported_birds_alive' => 'nullable|integer|min:0',
        ]);
        $data['source'] = 'mobile';
        $data['reported_at'] = now();

        $entry = DB::transaction(fn () => $recorder->record($flock, $data, $existing));

        return $this->ok($this->entry($entry), $existing ? 'Day updated.' : 'Day recorded.', [], $existing ? 200 : 201);
    }

    public function deleteEntry(DailyEntry $entry)
    {
        $this->authorizeTo('Delete daily-entry');
        $entry->delete();

        return $this->ok(null, 'Record deleted.');
    }

    private function flockRow(Flock $f, ?array $recent = null, ?string $last = null, ?int $issues = null): array
    {
        return array_filter([
            'id' => $f->id,
            'name' => $f->display_name,
            'type' => $f->flock_type,
            'type_label' => $f->type_label,
            'breed' => $f->breed,
            'house' => $f->house,
            'status' => $f->status,
            'age_weeks' => $f->age_weeks_now,
            'birds_start' => $f->initial_bird_count,
            'birds_now' => $f->current_bird_count,
            'date_of_arrival' => $f->date_of_arrival?->toDateString(),
            'merged_into_id' => $f->merged_into_id,
            'last_record' => $last ? substr((string) $last, 0, 10) : null,
            'recent' => $recent,
            'issues' => $issues,
        ], fn ($v) => $v !== null || true);
    }

    private function entry(DailyEntry $e): array
    {
        return [
            'id' => $e->id,
            'flock_id' => $e->flock_id,
            'entry_date' => $e->entry_date?->toDateString(),
            'week' => $e->weekEntry?->week_name,
            'day' => $e->day_number,
            'daily_mortality' => $e->daily_mortality,
            'birds_stolen' => $e->birds_stolen,
            'birds_culled' => $e->birds_culled,
            'sick_bay' => $e->sick_bay,
            'birds_alive' => $e->current_birds,
            'total_deaths' => $e->total_mortality,
            'daily_feeds' => (float) $e->daily_feeds,
            'available_feeds' => (float) $e->available_feeds,
            'eggs_produced' => $e->eggs_produced,
            'eggs_sold' => $e->eggs_sold,
            'broken_egg' => $e->broken_egg,
            'bad_eggs' => $e->bad_eggs,
            'egg_stock' => $e->egg_stock,
            'hen_day' => $e->hen_day,
            'drugs' => $e->drugs,
            'notes' => $e->notes,
            'avg_weight_g' => $e->avg_weight_g !== null ? (float) $e->avg_weight_g : null,
            'source' => $e->source,
            'updated_at' => $e->updated_at?->toIso8601String(),
        ];
    }
}
