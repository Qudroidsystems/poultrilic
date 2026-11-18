<?php

namespace App\Services;

use App\Models\LivestockRecord;
use App\Models\LivestockType;
use App\Models\LivestockUnit;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Totals for any livestock type, worked out from its own field definitions: each numeric field is totalled the
 * way the admin chose (sum, average, latest, highest, lowest, count), plus head count at the start and end.
 * Metric keys are "field:{key}" plus "head_start", "head_end", "records", "change_pct".
 */
class LivestockMetrics
{
    public function catalogue(LivestockType $type): array
    {
        $m = [
            'records' => ['Days recorded', 'days', 'int'],
            'head_start' => [$type->count_label . ' at start', '', 'int'],
            'head_end' => [$type->count_label . ' at end', '', 'int'],
            'change_pct' => ['Change in ' . strtolower($type->count_label), '%', 'pct1'],
        ];
        foreach ($type->numericFields() as $f) {
            $agg = $f->aggregate === 'sum' ? '' : ' (' . strtolower(\App\Models\LivestockField::AGGREGATES[$f->aggregate] ?? $f->aggregate) . ')';
            $m['field:' . $f->key] = [$f->label . $agg, $f->unit ?? '', $f->kind === 'money' ? 'money' : ($f->kind === 'number' ? 'int' : 'dec2')];
        }

        return $m;
    }

    public function series(LivestockType $type, ?array $unitIds = null, $from = null, $to = null, string $group = 'month'): array
    {
        $records = $this->records($type, $unitIds, $from, $to);
        if ($group === 'all') {
            $a = $from ? Carbon::parse($from) : $records->min('record_date');
            $b = $to ? Carbon::parse($to) : $records->max('record_date');

            return [['key' => 'all', 'label' => ($a && $b) ? $a->format('d M Y') . ' – ' . $b->format('d M Y') : 'All records'] + $this->compute($type, $records)];
        }

        $names = LivestockUnit::pluck('name', 'id');
        $out = [];
        foreach ($records->groupBy(fn ($r) => match ($group) {
            'day' => $r->record_date->toDateString(),
            'week' => $r->record_date->format('o-\WW'),
            'month' => $r->record_date->format('Y-m'),
            'unit', 'flock' => (string) $r->livestock_unit_id,
            default => 'all',
        })->sortKeys() as $key => $items) {
            $label = match ($group) {
                'day' => Carbon::parse($key)->format('d M Y'),
                'week' => 'Wk ' . substr($key, 6) . ' ' . substr($key, 0, 4),
                'month' => Carbon::parse($key . '-01')->format('M Y'),
                'unit', 'flock' => $names[(int) $key] ?? $key,
                default => (string) $key,
            };
            $out[] = ['key' => (string) $key, 'label' => $label] + $this->compute($type, $items);
        }

        return $out;
    }

    public function byUnitOverTime(LivestockType $type, string $metric, ?array $unitIds = null, $from = null, $to = null, string $group = 'month'): array
    {
        $labels = [];
        $sets = [];
        foreach ($type->units()->when($unitIds, fn ($q) => $q->whereIn('id', $unitIds))->get() as $u) {
            $rows = collect($this->series($type, [$u->id], $from, $to, $group))->keyBy('key');
            if ($rows->isEmpty()) {
                continue;
            }
            foreach ($rows as $k => $r) {
                $labels[$k] = $r['label'];
            }
            $sets[] = ['name' => $u->name, 'rows' => $rows];
        }
        ksort($labels);

        return [
            'labels' => array_values($labels),
            'datasets' => array_map(fn ($s) => ['name' => $s['name'], 'data' => array_map(fn ($k) => $s['rows'][$k][$metric] ?? null, array_keys($labels))], $sets),
        ];
    }

    private function records(LivestockType $type, ?array $unitIds, $from, $to): Collection
    {
        return LivestockRecord::query()
            ->whereHas('unit', fn ($q) => $q->where('livestock_type_id', $type->id))
            ->when($unitIds, fn ($q) => $q->whereIn('livestock_unit_id', $unitIds))
            ->when($from, fn ($q) => $q->where('record_date', '>=', Carbon::parse($from)->toDateString()))
            ->when($to, fn ($q) => $q->where('record_date', '<=', Carbon::parse($to)->toDateString()))
            ->orderBy('record_date')->orderBy('id')
            ->get();
    }

    private function compute(LivestockType $type, Collection $records): array
    {
        $fields = $type->fields;
        $start = 0;
        $end = 0;
        foreach ($records->groupBy('livestock_unit_id') as $rows) {
            $first = $rows->first();
            // Head count before the first record = its count after, with that day's changes undone.
            $before = (int) $first->count_after;
            $set = false;
            foreach ($fields as $f) {
                $v = $first->values[$f->key] ?? null;
                if (! is_numeric($v)) {
                    continue;
                }
                if ($f->headcount === 'add') {
                    $before -= (int) $v;
                } elseif ($f->headcount === 'subtract') {
                    $before += (int) $v;
                } elseif ($f->headcount === 'set') {
                    $set = true;
                }
            }
            if ($set) {
                $prev = LivestockRecord::where('livestock_unit_id', $first->livestock_unit_id)->where('record_date', '<', $first->record_date)->orderByDesc('record_date')->value('count_after');
                $before = $prev ?? (int) LivestockUnit::whereKey($first->livestock_unit_id)->value('opening_count');
            }
            $start += $before;
            $end += (int) $rows->last()->count_after;
        }

        $r = [
            'records' => $records->count(),
            'head_start' => $start,
            'head_end' => $end,
            'change_pct' => $start > 0 ? round(($end - $start) / $start * 100, 1) : null,
        ];

        foreach ($type->numericFields() as $f) {
            $vals = $records->map(fn ($rec) => $rec->values[$f->key] ?? null)->filter(fn ($v) => is_numeric($v))->map(fn ($v) => (float) $v);
            $r['field:' . $f->key] = $vals->isEmpty() ? ($f->aggregate === 'sum' || $f->aggregate === 'count' ? 0 : null) : match ($f->aggregate) {
                'sum' => round($vals->sum(), 2),
                'avg' => round($vals->avg(), 2),
                'last' => $vals->last(),
                'max' => $vals->max(),
                'min' => $vals->min(),
                'count' => $vals->count(),
                default => null,
            };
        }

        return $r;
    }

    public static function format(string $fmt, $v): string
    {
        if ($v === null) {
            return '–';
        }

        return match ($fmt) {
            'int' => number_format((float) $v),
            'money' => '₦' . number_format((float) $v, 2),
            'pct1' => number_format((float) $v, 1) . '%',
            default => rtrim(rtrim(number_format((float) $v, 2), '0'), '.'),
        };
    }
}
