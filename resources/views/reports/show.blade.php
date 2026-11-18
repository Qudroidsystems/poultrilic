@extends('layouts.page')
@php
    $rep = $r['report'];
    $cat = $r['catalogue'];
    $src = $rep->source;
    $groupName = ['all' => 'Period', 'day' => 'Day', 'week' => 'Week', 'month' => 'Month', 'flock' => $src === 'poultry' ? 'Flock' : 'Unit'][$rep->group_by] ?? '';
    $firstUnit = $r['metrics'] ? ($cat[$r['metrics'][0]][1] ?? '') : '';
    $units = collect($r['metrics'])->map(fn ($m) => $cat[$m][1])->unique();
    $q = request()->only(['period', 'from', 'to']);
@endphp
@section('page')
    @include('partials.page-header', ['icon' => 'ri-bar-chart-2-line', 'title' => $rep->name, 'subtitle' => (\App\Services\ReportRunner::sources()[$src] ?? $src) . ($r['entities'] ? ' · ' . implode(', ', $r['entities']) : '') . ($rep->description ? ' · ' . $rep->description : ''),
        'crumbs' => ['Reports' => route('reports.index'), $rep->name => null],
        'actions' => '<a href="' . route('reports.export', [$rep, 'xlsx'] + $q) . '" class="btn btn-sm btn-soft-success"><i class="ri-file-excel-2-line align-bottom"></i> Excel</a> '
            . '<a href="' . route('reports.export', [$rep, 'pdf'] + $q) . '" class="btn btn-sm btn-soft-danger"><i class="ri-file-pdf-line align-bottom"></i> PDF</a> '
            . '<a href="' . route('reports.export', [$rep, 'csv'] + $q) . '" class="btn btn-sm btn-light">CSV</a> '
            . (auth()->user()->can('Manage reports') ? '<a href="' . route('reports.edit', $rep) . '" class="btn btn-sm btn-primary"><i class="ri-pencil-line align-bottom"></i> Edit</a>' : '')])

    @include('partials.period-filter', ['period' => request('period', $rep->period), 'from' => $r['from'], 'to' => $r['to']])

    @if ($rep->chart !== 'table' && $r['metrics'] && count($r['rows']) > 0)
        <div class="card">
            <div class="card-body">
                @if ($units->count() > 1 && ! $rep->per_entity)
                    {{-- Different units never share an axis: one small chart per figure. --}}
                    <div class="row">
                        @foreach ($r['chart']['series'] as $i => $s)
                            <div class="col-lg-6">
                                <h6 class="fs-13 text-muted">{{ $s['name'] }}</h6>
                                @include('partials.chart', ['id' => 'rc' . $i, 'type' => $rep->chart, 'labels' => $r['chart']['labels'], 'series' => [$s], 'unit' => $cat[$r['metrics'][$i]][1], 'height' => 230])
                            </div>
                        @endforeach
                    </div>
                @else
                    @include('partials.chart', ['id' => 'rc', 'type' => $rep->chart, 'labels' => $r['chart']['labels'], 'series' => $r['chart']['series'], 'unit' => $firstUnit, 'height' => 340])
                @endif
            </div>
        </div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-0">
                <thead class="table-light"><tr><th>{{ $groupName }}</th>
                    @foreach ($r['metrics'] as $m)<th class="text-end">{{ $cat[$m][0] }}@if ($cat[$m][1] && ! in_array($cat[$m][1], ['%', '₦']))<br><span class="text-muted fw-normal fs-11">{{ $cat[$m][1] }}</span>@endif</th>@endforeach</tr></thead>
                <tbody>
                    @forelse ($r['rows'] as $row)
                        <tr><td class="text-nowrap">{{ $row['label'] }}</td>@foreach ($r['metrics'] as $m)<td class="text-end">{{ $runner->format($src, $m, $row[$m] ?? null) }}</td>@endforeach</tr>
                    @empty
                        <tr><td colspan="{{ count($r['metrics']) + 1 }}" class="text-center text-muted py-4">No records in this period.</td></tr>
                    @endforelse
                    @if (count($r['rows']) > 1)
                        <tr class="fw-semibold table-light"><td>Whole period</td>@foreach ($r['metrics'] as $m)<td class="text-end">{{ $runner->format($src, $m, $r['total'][$m] ?? null) }}</td>@endforeach</tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="card-footer text-muted fs-12">Rates (hen-day, mortality, eggs per bag) are worked out for each row from its own days, so the whole-period figure is not an average of the rows.</div>
    </div>
@endsection
