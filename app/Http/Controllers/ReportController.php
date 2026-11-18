<?php

namespace App\Http\Controllers;

use App\Models\DailyEntry;
use App\Models\Flock;
use App\Models\LivestockType;
use App\Models\ReportDefinition;
use App\Services\FlockLedger;
use App\Services\Import\SheetTools;
use App\Services\LivestockMetrics;
use App\Services\PoultryMetrics;
use App\Services\ReportRunner;
use App\Support\FarmSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function __construct(private ReportRunner $runner)
    {
        $this->middleware('permission:View reports', ['only' => ['index', 'show', 'export', 'performance']]);
        $this->middleware('permission:Manage reports', ['only' => ['create', 'store', 'edit', 'update', 'destroy']]);
    }

    /** Ready-made reports offered on an empty reports page. */
    public const STARTERS = [
        ['Monthly laying performance', 'poultry', ['eggs_laid', 'hen_day_pct', 'eggs_per_bag'], 'month', 'line', 'last_12m', false, 'Eggs, laying rate and feed efficiency month by month.'],
        ['Hen-day by flock (weekly)', 'poultry', ['hen_day_pct'], 'week', 'line', 'last_90', true, 'One line per flock: spot a flock whose laying rate is slipping.'],
        ['Mortality by month', 'poultry', ['deaths', 'mortality_pct', 'birds_stolen'], 'month', 'bar', 'last_12m', false, 'Deaths, mortality rate and missing birds.'],
        ['Feed use by flock', 'poultry', ['feed_bags', 'feed_g_bird_day', 'eggs_per_bag', 'feed_kg_per_dozen'], 'flock', 'bar', 'last_30', false, 'Compare feed efficiency between flocks.'],
        ['Daily records (last 30 days)', 'poultry', ['eggs_laid', 'eggs_sold', 'deaths', 'feed_bags', 'birds_end'], 'day', 'table', 'last_30', false, 'The day-by-day log for the whole farm.'],
        ['Money in and out', 'poultry', ['sales_invoiced', 'payments_received', 'expenses', 'net_cash'], 'month', 'bar', 'this_year', false, 'Sales, payments received and expenses by month.'],
    ];

    public function index(Request $request)
    {
        if ($request->boolean('starters') && $request->user()->can('Manage reports')) {
            foreach (self::STARTERS as [$name, $source, $metrics, $group, $chart, $period, $perEntity, $desc]) {
                ReportDefinition::firstOrCreate(['name' => $name], [
                    'source' => $source, 'metrics' => $metrics, 'group_by' => $group, 'chart' => $chart, 'period' => $period,
                    'per_entity' => $perEntity, 'description' => $desc, 'created_by' => $request->user()->id,
                ]);
            }
            foreach (LivestockType::where('active', true)->with('fields')->get() as $t) {
                $m = array_slice(array_keys(app(LivestockMetrics::class)->catalogue($t)), 2, 5);
                ReportDefinition::firstOrCreate(['name' => "{$t->name} by month"], [
                    'source' => 'livestock:' . $t->id, 'metrics' => $m, 'group_by' => 'month', 'chart' => 'bar', 'period' => 'last_12m',
                    'description' => "Head count and totals for {$t->name}.", 'created_by' => $request->user()->id,
                ]);
            }

            return redirect()->route('reports.index')->with('success', 'Starter reports added. Open one, or change it with Edit.');
        }

        return view('reports.index', [
            'pagetitle' => 'Reports',
            'reports' => ReportDefinition::with('creator')->orderByDesc('pinned')->orderBy('name')->get(),
            'sources' => ReportRunner::sources(),
        ]);
    }

    public function create(Request $request)
    {
        $report = new ReportDefinition([
            'source' => $request->input('source', 'poultry'), 'metrics' => [], 'group_by' => 'month',
            'chart' => 'line', 'period' => 'last_90',
        ]);

        return $this->form($report);
    }

    public function edit(Request $request, ReportDefinition $report)
    {
        if ($request->filled('source') && $request->input('source') !== $report->source) {
            $report->source = $request->input('source');
            $report->metrics = [];
        }

        return $this->form($report);
    }

    private function form(ReportDefinition $report)
    {
        $sources = ReportRunner::sources();
        if (! isset($sources[$report->source])) {
            $report->source = 'poultry';
        }

        return view('reports.form', [
            'pagetitle' => $report->exists ? "Edit {$report->name}" : 'New report',
            'report' => $report,
            'sources' => $sources,
            'catalogue' => $this->runner->catalogue($report->source),
            'entities' => $this->runner->entities($report->source),
        ]);
    }

    public function store(Request $request)
    {
        $report = ReportDefinition::create($this->validated($request) + ['created_by' => $request->user()->id]);

        return redirect()->route('reports.show', $report)->with('success', 'Report saved.');
    }

    public function update(Request $request, ReportDefinition $report)
    {
        $report->update($this->validated($request));

        return redirect()->route('reports.show', $report)->with('success', 'Report saved.');
    }

    public function destroy(ReportDefinition $report)
    {
        $report->delete();

        return redirect()->route('reports.index')->with('success', 'Report deleted.');
    }

    private function validated(Request $request): array
    {
        $source = $request->input('source', 'poultry');
        $catalogue = $this->runner->catalogue(array_key_exists($source, ReportRunner::sources()) ? $source : 'poultry');
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:500',
            'source' => ['required', Rule::in(array_keys(ReportRunner::sources()))],
            'metrics' => 'required|array|min:1|max:8',
            'metrics.*' => [Rule::in(array_keys($catalogue))],
            'group_by' => ['required', Rule::in(['all', 'day', 'week', 'month', 'flock'])],
            'chart' => ['required', Rule::in(array_keys(ReportDefinition::CHARTS))],
            'period' => ['required', Rule::in(array_keys(ReportDefinition::PERIODS))],
            'date_from' => 'nullable|required_if:period,custom|date',
            'date_to' => 'nullable|required_if:period,custom|date|after_or_equal:date_from',
            'ids' => 'nullable|array',
            'ids.*' => 'integer',
            'per_entity' => 'nullable|boolean',
            'pinned' => 'nullable|boolean',
        ], ['metrics.required' => 'Pick at least one figure to report.']);

        $data['filters'] = ['ids' => array_values(array_map('intval', $data['ids'] ?? []))];
        unset($data['ids']);
        $data['per_entity'] = $request->boolean('per_entity');
        $data['pinned'] = $request->boolean('pinned');

        return $data;
    }

    public function show(Request $request, ReportDefinition $report)
    {
        $result = $this->runner->run($this->withPeriod($request, $report));

        return view('reports.show', ['pagetitle' => $report->name, 'r' => $result, 'runner' => $this->runner]);
    }

    public function export(Request $request, ReportDefinition $report, string $format)
    {
        $r = $this->runner->run($this->withPeriod($request, $report));
        $name = str($report->name)->slug() . '-' . now()->format('Y-m-d');

        if ($format === 'pdf') {
            return Pdf::loadView('reports.pdf.report', ['r' => $r, 'runner' => $this->runner, 'farm' => FarmSettings::text('farm_name')])
                ->setPaper('a4', count($r['metrics']) > 5 ? 'landscape' : 'portrait')->download("{$name}.pdf");
        }

        $headers = [[$this->groupLabel($report->group_by), null]];
        foreach ($r['metrics'] as $m) {
            [$label, $unit] = $r['catalogue'][$m];
            $headers[] = [$unit && ! in_array($unit, ['%'], true) ? "{$label} ({$unit})" : ($unit === '%' ? "{$label} %" : $label), null];
        }
        $rows = array_map(fn ($row) => array_merge([$row['label']], array_map(fn ($m) => $row[$m] ?? null, $r['metrics'])), $r['rows']);
        $rows[] = array_merge(['Whole period'], array_map(fn ($m) => $r['total'][$m] ?? null, $r['metrics']));

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($headers, $rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, array_column($headers, 0));
                foreach ($rows as $row) {
                    fputcsv($out, $row);
                }
                fclose($out);
            }, "{$name}.csv", ['Content-Type' => 'text/csv']);
        }

        $book = SheetTools::workbook('Report', $headers, $rows, [
            $report->name,
            ReportRunner::sources()[$report->source] ?? $report->source,
            'Period: ' . ($r['from'] ? Carbon::parse($r['from'])->format('d M Y') : 'first record') . ' to ' . ($r['to'] ? Carbon::parse($r['to'])->format('d M Y') : 'latest record'),
            'Generated ' . now()->format('d M Y H:i') . ' by Poultrilic',
        ]);
        $last = $book->getActiveSheet()->getHighestRow();
        $book->getActiveSheet()->getStyle("A{$last}:" . $book->getActiveSheet()->getHighestColumn() . $last)->getFont()->setBold(true);

        return SheetTools::download($book, "{$name}.xlsx");
    }

    /**
     * The farm performance report: status, laying, mortality, feed, data quality, livestock, money and
     * plain-language observations, for any period and set of flocks. ?format=pdf downloads it.
     */
    public function performance(Request $request, PoultryMetrics $metrics, LivestockMetrics $lm)
    {
        [$period, $from, $to, $anchor] = PoultryController::period($request, 'all');
        $ids = array_values(array_filter(array_map('intval', (array) $request->input('flocks', [])))) ?: null;

        $flocks = Flock::query()->when($ids, fn ($q) => $q->whereIn('id', $ids))->orderBy('id')->get()
            ->filter(fn ($f) => DailyEntry::forFlock($f->id)
                ->when($from, fn ($q) => $q->where('entry_date', '>=', $from))->when($to, fn ($q) => $q->where('entry_date', '<=', $to))->exists())
            ->values();
        $fids = $flocks->pluck('id')->all() ?: [0];

        $byFlock = [];
        foreach ($flocks as $f) {
            $byFlock[$f->id] = [
                'flock' => $f,
                'summary' => $metrics->summary([$f->id], $from, $to),
                'monthly' => collect($metrics->series([$f->id], $from, $to, 'month'))->keyBy('key'),
                'issues' => collect(FlockLedger::issues($f))
                    ->filter(fn ($i) => ! $i['date'] || ((! $from || $i['date'] >= $from) && (! $to || $i['date'] <= $to)))->values(),
            ];
        }
        $months = collect($byFlock)->flatMap(fn ($b) => $b['monthly']->map(fn ($r) => $r['label'])->all())->unique()->sortKeys()->all();

        $livestock = LivestockType::where('active', true)->with('fields')->get()->map(fn ($t) => [
            'type' => $t, 'catalogue' => $lm->catalogue($t),
            'total' => $lm->series($t, null, $from, $to, 'all')[0],
            'units' => $lm->series($t, null, $from, $to, 'unit'),
        ])->filter(fn ($x) => $x['total']['records'] > 0)->values();

        $data = [
            'pagetitle' => 'Farm performance report',
            'farm' => FarmSettings::text('farm_name'),
            'period' => $period, 'from' => $from, 'to' => $to,
            'selected' => $ids ?? [],
            'allFlocks' => Flock::orderBy('name')->get(),
            'summary' => $metrics->summary($fids, $from, $to),
            'byFlock' => $byFlock,
            'months' => $months,
            'henWeekly' => $metrics->byFlockOverTime('hen_day_pct', $fids, $from, $to, 'week'),
            'perBag' => $metrics->byFlockOverTime('eggs_per_bag', $fids, $from, $to, 'month'),
            'mortality' => $metrics->byFlockOverTime('mortality_pct', $fids, $from, $to, 'month'),
            'monthlyFarm' => $metrics->series($fids, $from, $to, 'month'),
            'observations' => $metrics->observations($fids, $from, $to),
            'livestock' => $livestock,
            'birdsNow' => (int) Flock::active()->sum('current_bird_count'),
            'generated' => now(),
        ];

        if ($request->input('format') === 'pdf') {
            return Pdf::loadView('reports.pdf.performance', $data)->setPaper('a4', 'portrait')
                ->download('farm-performance-report-' . now()->format('Y-m-d') . '.pdf');
        }

        return view('reports.performance', $data);
    }

    /** The report with the period picked on the page (not saved). */
    private function withPeriod(Request $request, ReportDefinition $report): ReportDefinition
    {
        if (! $request->filled('period') || ! array_key_exists($request->input('period'), ReportDefinition::PERIODS)) {
            return $report;
        }
        $tmp = $report->replicate();
        $tmp->id = $report->id;
        $tmp->exists = true;
        $tmp->period = $request->input('period');
        $tmp->date_from = $request->input('from') ?: null;
        $tmp->date_to = $request->input('to') ?: null;

        return $tmp;
    }

    public function groupLabel(string $g): string
    {
        return ['all' => 'Period', 'day' => 'Day', 'week' => 'Week', 'month' => 'Month', 'flock' => 'Flock / unit'][$g] ?? $g;
    }
}
