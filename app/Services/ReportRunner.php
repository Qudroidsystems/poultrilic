<?php

namespace App\Services;

use App\Models\DailyEntry;
use App\Models\Flock;
use App\Models\LivestockRecord;
use App\Models\LivestockType;
use App\Models\LivestockUnit;
use App\Models\ReportDefinition;
use Carbon\Carbon;

/**
 * Runs a saved report definition against poultry or a livestock type and returns everything a page, PDF,
 * spreadsheet or the API needs: columns, rows, totals and chart series.
 */
class ReportRunner
{
    public function __construct(private PoultryMetrics $poultry, private LivestockMetrics $livestock)
    {
    }

    /** Sources an admin can report on: key => label. */
    public static function sources(): array
    {
        $s = ['poultry' => 'Poultry (all flocks)'];
        foreach (LivestockType::orderBy('sort')->get() as $t) {
            $s['livestock:' . $t->id] = $t->name;
        }

        return $s;
    }

    /** Metric catalogue for a source: key => [label, unit, format]. */
    public function catalogue(string $source): array
    {
        if ($source === 'poultry') {
            return collect(PoultryMetrics::METRICS)->map(fn ($m) => [$m[0], $m[1], $m[2]])->all();
        }
        $type = LivestockType::with('fields')->findOrFail((int) substr($source, 10));

        return $this->livestock->catalogue($type);
    }

    /** Flocks or units that can be filtered: id => name. */
    public function entities(string $source): array
    {
        if ($source === 'poultry') {
            return Flock::orderByRaw("CASE WHEN status='active' THEN 0 ELSE 1 END")->orderBy('name')->get()
                ->mapWithKeys(fn ($f) => [$f->id => $f->display_name . ($f->status !== 'active' ? ' (closed)' : '')])->all();
        }

        return LivestockUnit::where('livestock_type_id', (int) substr($source, 10))->orderBy('name')->pluck('name', 'id')->all();
    }

    public function run(ReportDefinition $report, ?string $from = null, ?string $to = null): array
    {
        $source = $report->source;
        [$pf, $pt] = $report->range($this->anchor($source));
        $from ??= $pf;
        $to ??= $pt;
        $ids = array_values(array_filter(array_map('intval', $report->filters['ids'] ?? []))) ?: null;
        $catalogue = $this->catalogue($source);
        $metrics = array_values(array_filter($report->metrics ?? [], fn ($m) => isset($catalogue[$m])));
        $group = $report->group_by;

        if ($source === 'poultry') {
            $rows = $this->poultry->series($ids, $from, $to, $group);
            $total = $this->poultry->summary($ids, $from, $to);
        } else {
            $type = LivestockType::with('fields')->findOrFail((int) substr($source, 10));
            $rows = $this->livestock->series($type, $ids, $from, $to, $group === 'flock' ? 'unit' : $group);
            $total = $this->livestock->series($type, $ids, $from, $to, 'all')[0];
        }

        // Chart: one series per metric, or one per flock/unit for the first metric.
        $chart = ['labels' => array_column($rows, 'label'), 'series' => []];
        if ($report->per_entity && $metrics && ! in_array($group, ['flock', 'unit', 'all'], true)) {
            $m = $metrics[0];
            $by = $source === 'poultry'
                ? $this->poultry->byFlockOverTime($m, $ids, $from, $to, $group)
                : $this->livestock->byUnitOverTime($type, $m, $ids, $from, $to, $group);
            $chart = ['labels' => $by['labels'], 'series' => array_map(fn ($d) => ['name' => $d['name'], 'data' => $d['data']], $by['datasets'])];
        } else {
            foreach ($metrics as $m) {
                $chart['series'][] = ['name' => $catalogue[$m][0] . ($catalogue[$m][1] && ! in_array($catalogue[$m][1], ['%', '₦'], true) ? " ({$catalogue[$m][1]})" : ''), 'data' => array_map(fn ($r) => $r[$m] ?? null, $rows)];
            }
        }

        return [
            'report' => $report,
            'from' => $from, 'to' => $to,
            'catalogue' => $catalogue,
            'metrics' => $metrics,
            'rows' => $rows,
            'total' => $total,
            'chart' => $chart,
            'entities' => $ids ? array_intersect_key($this->entities($source), array_flip($ids)) : [],
        ];
    }

    public function format(string $source, string $metric, $value): string
    {
        $fmt = $this->catalogue($source)[$metric][2] ?? 'dec1';
        if ($source === 'poultry') {
            return PoultryMetrics::format($metric, $value);
        }

        return LivestockMetrics::format($fmt, $value);
    }

    /** Relative periods end on the latest record for the source. */
    private function anchor(string $source): ?string
    {
        $latest = $source === 'poultry'
            ? DailyEntry::max('entry_date')
            : LivestockRecord::whereHas('unit', fn ($q) => $q->where('livestock_type_id', (int) substr($source, 10)))->max('record_date');

        return $latest ? min(Carbon::parse($latest), now())->toDateString() : null;
    }
}
