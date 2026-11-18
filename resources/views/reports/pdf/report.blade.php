@php
    $rep = $r['report'];
    $cat = $r['catalogue'];
    $src = $rep->source;
    $first = $r['metrics'][0] ?? null;
    $max = $first ? collect($r['rows'])->max(fn ($row) => is_numeric($row[$first] ?? null) ? abs($row[$first]) : 0) : 0;
@endphp
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>{{ $rep->name }}</title>
<style>
    @page { margin: 18mm 12mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1f2937; }
    h1 { font-size: 17px; color: #405189; margin: 0 0 2px; }
    .muted { color: #6b7280; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th { background: #eef1f8; text-align: left; }
    th, td { padding: 3px 5px; border-bottom: 0.5px solid #d9dde6; }
    .r { text-align: right; }
    .bar { height: 7px; background: #2a78d6; border-radius: 0 2px 2px 0; }
    .total td { font-weight: bold; background: #f6f7fb; }
</style></head>
<body>
<h1>{{ $rep->name }}</h1>
<div class="muted">{{ $farm }} · {{ \App\Services\ReportRunner::sources()[$src] ?? $src }} · {{ $r['from'] ? \Carbon\Carbon::parse($r['from'])->format('j M Y') : 'first record' }} – {{ $r['to'] ? \Carbon\Carbon::parse($r['to'])->format('j M Y') : 'latest record' }}
    @if ($r['entities']) · {{ implode(', ', $r['entities']) }} @endif · generated {{ now()->format('j M Y H:i') }}</div>
@if ($rep->description)<p>{{ $rep->description }}</p>@endif
<table>
    <tr><th>{{ ['all' => 'Period', 'day' => 'Day', 'week' => 'Week', 'month' => 'Month', 'flock' => 'Flock / unit'][$rep->group_by] ?? '' }}</th>
        @foreach ($r['metrics'] as $m)<th class="r">{{ $cat[$m][0] }}{{ $cat[$m][1] && ! in_array($cat[$m][1], ['%', '₦']) ? ' (' . $cat[$m][1] . ')' : '' }}</th>@endforeach
        @if ($first && count($r['metrics']) <= 4)<th style="width: 22%">{{ $cat[$first][0] }}</th>@endif</tr>
    @foreach ($r['rows'] as $row)
        <tr><td>{{ $row['label'] }}</td>@foreach ($r['metrics'] as $m)<td class="r">{{ $runner->format($src, $m, $row[$m] ?? null) }}</td>@endforeach
            @if ($first && count($r['metrics']) <= 4)<td><div class="bar" style="width: {{ $max > 0 && is_numeric($row[$first] ?? null) ? max(1, round(abs($row[$first]) / $max * 100)) : 0 }}%"></div></td>@endif</tr>
    @endforeach
    @if (count($r['rows']) > 1)
        <tr class="total"><td>Whole period</td>@foreach ($r['metrics'] as $m)<td class="r">{{ $runner->format($src, $m, $r['total'][$m] ?? null) }}</td>@endforeach @if ($first && count($r['metrics']) <= 4)<td></td>@endif</tr>
    @endif
</table>
</body></html>
