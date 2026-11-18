@extends('layouts.page')
@php
    use App\Services\PoultryMetrics as M;
    $s = $summary;
    $f = fn ($k) => M::format($k, $s[$k] ?? null);
    $sevIcon = ['danger' => 'ri-error-warning-fill text-danger', 'warning' => 'ri-alert-fill text-warning', 'info' => 'ri-information-fill text-info'];
    $sevLabel = ['danger' => 'Act now', 'warning' => 'Check', 'info' => 'Note'];
@endphp
@section('page')
    @include('partials.page-header', ['icon' => 'ri-dashboard-2-line', 
        'title' => 'Farm overview',
        'subtitle' => $activeCount . ' active flock' . ($activeCount === 1 ? '' : 's') . ' · ' . number_format($birdsNow) . ' birds on the farm',
        'actions' => (auth()->user()->can('Create daily-entry') || auth()->user()->can('Import records') ? '<a href="' . route('imports.index') . '" class="btn btn-soft-primary btn-sm"><i class="ri-file-excel-2-line align-bottom me-1"></i>Import Excel</a> ' : '')
            . (auth()->user()->can('View reports') ? '<a href="' . route('reports.performance', request()->query()) . '" class="btn btn-primary btn-sm"><i class="ri-file-chart-line align-bottom me-1"></i>Performance report</a>' : ''),
    ])

    @include('partials.period-filter', [
        'entities' => $allFlocks->mapWithKeys(fn ($fl) => [$fl->id => $fl->display_name . ($fl->status !== 'active' ? ' (closed)' : '')])->all(),
        'selected' => $flockIds,
    ])

    @if ($s['days_reported'] === 0)
        <div class="alert alert-info">No flock records in this period yet. Record a day from a flock's weeks page, or
            <a href="{{ route('imports.index') }}" class="alert-link">import an Excel sheet</a>.</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">@include('partials.kpi', ['label' => 'Eggs laid', 'value' => $f('eggs_laid'), 'sub' => number_format($s['crates_laid'], 1) . ' crates · ' . $f('eggs_sold') . ' sold', 'icon' => 'ri-donut-chart-line', 'tone' => 'warning'])</div>
        <div class="col-6 col-xl-3">@include('partials.kpi', ['label' => 'Hen-day production', 'value' => $f('hen_day_pct'), 'sub' => 'eggs per bird alive, laying flocks', 'icon' => 'ri-line-chart-line', 'tone' => 'success'])</div>
        <div class="col-6 col-xl-3">@include('partials.kpi', ['label' => 'Deaths', 'value' => $f('deaths'), 'sub' => $f('mortality_pct') . ' of birds' . ($s['birds_stolen'] ? ' · ' . number_format($s['birds_stolen']) . ' stolen/missing' : ''), 'icon' => 'ri-heart-pulse-line', 'tone' => ($s['mortality_pct'] ?? 0) > 3 ? 'danger' : 'info'])</div>
        <div class="col-6 col-xl-3">@include('partials.kpi', ['label' => 'Eggs per bag of feed', 'value' => $f('eggs_per_bag'), 'sub' => $f('feed_bags') . ' bags · ' . $f('feed_g_bird_day') . ' g/bird/day', 'icon' => 'ri-restaurant-line', 'tone' => 'primary'])</div>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">@include('partials.kpi', ['label' => 'Birds now', 'value' => number_format($birdsNow), 'sub' => 'active flocks, from the daily losses'])</div>
        <div class="col-6 col-xl-3">@include('partials.kpi', ['label' => 'Eggs in farm', 'value' => $s['egg_stock_end'] >= 0 ? number_format($s['egg_stock_end']) : '–',
            'sub' => $s['egg_stock_end'] >= 0 ? \App\Support\Eggs::format($s['egg_stock_end']) . ' at the end of the period' : 'More eggs sold than recorded as laid; see each flock\'s data checks'])</div>
        <div class="col-6 col-xl-3">@include('partials.kpi', ['label' => 'Feed in store', 'value' => $feed['tracking'] ? number_format($feed['on_hand'], 1) . ' bags' : '–', 'sub' => $feed['tracking'] ? ($feed['days_remaining'] !== null ? 'about ' . $feed['days_remaining'] . ' days' : '') : 'Record feed purchases to track stock'])</div>
        <div class="col-6 col-xl-3">@include('partials.kpi', ['label' => 'Payments − expenses', 'value' => $f('net_cash'), 'sub' => $f('payments_received') . ' in · ' . $f('expenses') . ' out'])</div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header d-flex align-items-center"><h5 class="card-title mb-0 flex-grow-1">Eggs laid</h5><span class="text-muted fs-12">all selected flocks, per {{ count($series) && str_starts_with($series[0]['key'], '20') && strlen($series[0]['key']) === 10 ? 'day' : 'week' }}</span></div>
                <div class="card-body">
                    @include('partials.chart', ['id' => 'eggsChart', 'type' => 'bar', 'labels' => array_column($series, 'label'), 'series' => [['name' => 'Eggs laid', 'data' => array_column($series, 'eggs_laid')]], 'unit' => 'eggs', 'height' => 280])
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">What needs attention</h5></div>
                <div class="card-body p-0" style="max-height: 352px; overflow-y: auto">
                    @forelse ($observations as [$sev, $title, $detail])
                        <div class="d-flex gap-2 px-3 py-2 border-bottom">
                            <i class="{{ $sevIcon[$sev] ?? $sevIcon['info'] }} fs-16 mt-1" aria-hidden="true"></i>
                            <div>
                                <div class="fw-medium fs-13">{{ $title }} <span class="badge bg-light text-muted ms-1">{{ $sevLabel[$sev] ?? 'Note' }}</span></div>
                                <div class="text-muted fs-12">{{ $detail }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted p-3 mb-0"><i class="ri-checkbox-circle-line text-success me-1"></i>Nothing unusual in the latest records.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Hen-day production by flock (weekly)</h5></div>
                <div class="card-body">
                    @include('partials.chart', ['id' => 'henChart', 'type' => 'line', 'labels' => $henDay['labels'], 'series' => array_map(fn ($d) => ['name' => $d['name'], 'data' => $d['data']], $henDay['datasets']), 'unit' => '%', 'height' => 280])
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header d-flex"><h5 class="card-title mb-0 flex-grow-1">Deaths</h5><span class="text-muted fs-12">birds, same periods as eggs</span></div>
                <div class="card-body">
                    @include('partials.chart', ['id' => 'deathChart', 'type' => 'bar', 'labels' => array_column($series, 'label'), 'series' => [['name' => 'Deaths', 'data' => array_column($series, 'deaths')]], 'unit' => 'birds', 'height' => 280])
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h5 class="card-title mb-0 flex-grow-1">Active flocks</h5>
            <span class="text-muted fs-12">laying rate and deaths are for each flock's latest 30 days of records</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Flock</th><th>Type</th><th class="text-end">Age</th><th class="text-end">Birds now</th>
                        <th class="text-end">Hen-day</th><th class="text-end">Eggs / bag</th><th class="text-end">Deaths</th>
                        <th class="text-end">Mortality to date</th><th>Last record</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($table as $row)
                        @php($fl = $row['flock'])
                        <tr>
                            <td><a href="{{ route('poultry.flocks.show', $fl) }}" class="fw-medium">{{ $fl->display_name }}</a>@if ($fl->house)<div class="text-muted fs-12">{{ $fl->house }}</div>@endif</td>
                            <td>{{ $fl->type_label }}</td>
                            <td class="text-end">{{ $fl->age_weeks_now ? $fl->age_weeks_now . ' wks' : '–' }}</td>
                            <td class="text-end">{{ number_format($fl->current_bird_count) }}</td>
                            <td class="text-end">{{ M::format('hen_day_pct', $row['recent']['hen_day_pct'] ?? null) }}</td>
                            <td class="text-end">{{ M::format('eggs_per_bag', $row['recent']['eggs_per_bag'] ?? null) }}</td>
                            <td class="text-end">{{ M::format('deaths', $row['recent']['deaths'] ?? null) }}</td>
                            <td class="text-end">{{ M::format('mortality_pct', $row['lifetime']['mortality_pct']) }}</td>
                            <td>
                                @if ($row['last'])
                                    {{ \Carbon\Carbon::parse($row['last'])->format('j M Y') }}
                                    @php($ago = (int) \Carbon\Carbon::parse($row['last'])->diffInDays(now()->startOfDay()))
                                    @if ($ago > 2)<span class="badge bg-warning-subtle text-warning ms-1">{{ $ago }} days ago</span>@endif
                                @else
                                    <span class="text-muted">No records</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @if ($row['issues'])<a href="{{ route('poultry.flocks.show', $fl) }}#issues" class="badge bg-info-subtle text-info me-1" title="Data checks">{{ $row['issues'] }} checks</a>@endif
                                <a href="{{ route('week-entries.index', $fl->id) }}" class="btn btn-sm btn-soft-secondary">Records</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">No active flocks. <a href="{{ route('flocks.index') }}">Add a flock</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($pinned->isNotEmpty())
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Pinned reports</h5></div>
            <div class="card-body d-flex flex-wrap gap-2">
                @foreach ($pinned as $rep)
                    <a href="{{ route('reports.show', $rep) }}" class="btn btn-soft-primary btn-sm"><i class="ri-bar-chart-2-line align-bottom me-1"></i>{{ $rep->name }}</a>
                @endforeach
            </div>
        </div>
    @endif
@endsection
