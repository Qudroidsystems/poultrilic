@extends('layouts.page')
@php
    use App\Services\PoultryMetrics as M;
    use App\Services\LivestockMetrics as LM;
    $s = $summary;
    $sevIcon = ['danger' => 'ri-error-warning-fill text-danger', 'warning' => 'ri-alert-fill text-warning', 'info' => 'ri-information-fill text-info'];
    $sevLabel = ['danger' => 'Act now', 'warning' => 'Check', 'info' => 'Note'];
@endphp
@section('page')
    @include('partials.page-header', ['icon' => 'ri-file-chart-line', 'title' => 'Farm performance report', 'subtitle' => ($farm ? $farm . ' · ' : '') . 'generated ' . $generated->format('j M Y, H:i'),
        'crumbs' => ['Reports' => route('reports.index'), 'Performance' => null],
        'actions' => '<a href="' . route('reports.performance', request()->query() + ['format' => 'pdf']) . '" class="btn btn-sm btn-danger"><i class="ri-file-pdf-line align-bottom me-1"></i>Download PDF</a>'])

    @include('partials.period-filter', ['entities' => $allFlocks->mapWithKeys(fn ($f) => [$f->id => $f->display_name])->all(), 'selected' => $selected])

    {{-- 1. At a glance --}}
    <h5 class="mb-3">1. At a glance</h5>
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-2">@include('partials.kpi', ['label' => 'Days of records', 'value' => number_format($s['days_reported']), 'sub' => count($byFlock) . ' flock(s)'])</div>
        <div class="col-6 col-xl-2">@include('partials.kpi', ['label' => 'Eggs laid', 'value' => M::format('eggs_laid', $s['eggs_laid']), 'sub' => number_format($s['crates_laid'], 1) . ' crates'])</div>
        <div class="col-6 col-xl-2">@include('partials.kpi', ['label' => 'Hen-day', 'value' => M::format('hen_day_pct', $s['hen_day_pct']), 'sub' => 'laying flocks'])</div>
        <div class="col-6 col-xl-2">@include('partials.kpi', ['label' => 'Deaths', 'value' => M::format('deaths', $s['deaths']), 'sub' => M::format('mortality_pct', $s['mortality_pct']) . ' of birds'])</div>
        <div class="col-6 col-xl-2">@include('partials.kpi', ['label' => 'Stolen / missing', 'value' => number_format($s['birds_stolen']), 'sub' => 'birds'])</div>
        <div class="col-6 col-xl-2">@include('partials.kpi', ['label' => 'Birds now', 'value' => number_format($birdsNow), 'sub' => 'active flocks'])</div>
    </div>

    {{-- 2. Findings --}}
    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">2. What the records say</h5></div>
        <div class="card-body p-0">
            @forelse ($observations as [$sev, $title, $detail])
                <div class="d-flex gap-2 px-3 py-2 border-bottom"><i class="{{ $sevIcon[$sev] }} fs-16" aria-hidden="true"></i>
                    <div><span class="fw-medium">{{ $title }}</span> <span class="badge bg-light text-muted">{{ $sevLabel[$sev] }}</span><div class="text-muted fs-13">{{ $detail }}</div></div></div>
            @empty
                <p class="p-3 mb-0 text-muted">Nothing unusual found.</p>
            @endforelse
        </div>
    </div>

    {{-- 3. Flocks --}}
    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">3. Flocks in this period</h5></div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light"><tr><th>Flock</th><th>Records</th><th class="text-end">Birds at start</th><th class="text-end">Deaths</th><th class="text-end">Stolen</th><th class="text-end">Birds at end</th><th class="text-end">Mortality</th><th class="text-end">Eggs laid</th><th class="text-end">Hen-day</th><th class="text-end">Eggs / bag</th><th class="text-end">g / bird / day</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach ($byFlock as $b)
                        @php($fs = $b['summary'])
                        <tr>
                            <td><a href="{{ route('poultry.flocks.show', $b['flock']) }}">{{ $b['flock']->display_name }}</a></td>
                            <td class="fs-12">{{ $b['monthly']->first()['label'] ?? '' }} – {{ $b['monthly']->last()['label'] ?? '' }}</td>
                            <td class="text-end">{{ number_format($fs['birds_start']) }}</td><td class="text-end">{{ number_format($fs['deaths']) }}</td><td class="text-end">{{ number_format($fs['birds_stolen']) }}</td>
                            <td class="text-end">{{ number_format($fs['birds_end']) }}</td><td class="text-end">{{ M::format('mortality_pct', $fs['mortality_pct']) }}</td>
                            <td class="text-end">{{ number_format($fs['eggs_laid']) }}</td><td class="text-end">{{ M::format('hen_day_pct', $fs['hen_day_pct']) }}</td>
                            <td class="text-end">{{ M::format('eggs_per_bag', $fs['eggs_per_bag']) }}</td><td class="text-end">{{ M::format('feed_g_bird_day', $fs['feed_g_bird_day']) }}</td>
                            <td>{{ $b['flock']->merged_into_id ? 'Combined into ' . optional($b['flock']->mergedInto)->display_name : ucfirst($b['flock']->status) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- 4-6. Trends --}}
    <div class="row g-3">
        <div class="col-xl-6"><div class="card"><div class="card-header"><h5 class="card-title mb-0">4. Hen-day production, weekly</h5></div><div class="card-body">
            @include('partials.chart', ['id' => 'pHen', 'labels' => $henWeekly['labels'], 'series' => array_map(fn ($d) => ['name' => $d['name'], 'data' => $d['data']], $henWeekly['datasets']), 'unit' => '%', 'height' => 280])
        </div></div></div>
        <div class="col-xl-6"><div class="card"><div class="card-header"><h5 class="card-title mb-0">5. Eggs per bag of feed, monthly</h5></div><div class="card-body">
            @include('partials.chart', ['id' => 'pBag', 'labels' => $perBag['labels'], 'series' => array_map(fn ($d) => ['name' => $d['name'], 'data' => $d['data']], $perBag['datasets']), 'unit' => 'eggs', 'height' => 280])
        </div></div></div>
        <div class="col-xl-6"><div class="card"><div class="card-header"><h5 class="card-title mb-0">6. Mortality, monthly</h5></div><div class="card-body">
            @include('partials.chart', ['id' => 'pMort', 'type' => 'bar', 'labels' => $mortality['labels'], 'series' => array_map(fn ($d) => ['name' => $d['name'], 'data' => $d['data']], $mortality['datasets']), 'unit' => '%', 'height' => 280])
        </div></div></div>
        <div class="col-xl-6"><div class="card"><div class="card-header"><h5 class="card-title mb-0">7. Eggs laid, monthly (all flocks)</h5></div><div class="card-body">
            @include('partials.chart', ['id' => 'pEggs', 'type' => 'bar', 'labels' => array_column($monthlyFarm, 'label'), 'series' => [['name' => 'Eggs laid', 'data' => array_column($monthlyFarm, 'eggs_laid')]], 'unit' => 'eggs', 'height' => 280])
        </div></div></div>
    </div>

    {{-- 8. Data quality --}}
    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">8. Data checks</h5></div>
        <div class="card-body">
            <p class="text-muted fs-13">Where the farm's own totals disagree with the daily figures, days are missing or reports arrived late.</p>
            @foreach ($byFlock as $b)
                @if ($b['issues']->isNotEmpty())
                    <h6 class="mt-3">{{ $b['flock']->display_name }} <span class="badge bg-light text-muted">{{ $b['issues']->count() }}</span></h6>
                    <ul class="fs-13 mb-0">
                        @foreach ($b['issues']->take(8) as $i)<li>@if ($i['date']){{ \Carbon\Carbon::parse($i['date'])->format('j M Y') }}: @endif{{ $i['message'] }}</li>@endforeach
                        @if ($b['issues']->count() > 8)<li class="text-muted"><a href="{{ route('poultry.flocks.show', $b['flock']) }}#issues">{{ $b['issues']->count() - 8 }} more on the flock page</a></li>@endif
                    </ul>
                @endif
            @endforeach
        </div>
    </div>

    {{-- 9. Livestock --}}
    @foreach ($livestock as $ls)
        @php($t = $ls['type'])
        @php($keys = collect($ls['catalogue'])->keys()->filter(fn ($k) => str_starts_with($k, 'field:'))->values())
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">{{ $t->name }}</h5></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>{{ $t->unit_label }}</th><th class="text-end">At start</th><th class="text-end">At end</th>@foreach ($keys as $k)<th class="text-end">{{ $ls['catalogue'][$k][0] }}</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach ($ls['units'] as $u)
                            <tr><td>{{ $u['label'] }}</td><td class="text-end">{{ number_format($u['head_start']) }}</td><td class="text-end">{{ number_format($u['head_end']) }}</td>@foreach ($keys as $k)<td class="text-end">{{ LM::format($ls['catalogue'][$k][2], $u[$k] ?? null) }}</td>@endforeach</tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    {{-- 10. Money --}}
    @if ($s['sales_invoiced'] || $s['payments_received'] || $s['expenses'])
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Money in the period</h5></div>
            <div class="card-body row text-center">
                <div class="col"><div class="text-muted fs-12">Sales invoiced</div><div class="fs-16 fw-semibold">{{ M::format('sales_invoiced', $s['sales_invoiced']) }}</div></div>
                <div class="col"><div class="text-muted fs-12">Payments received</div><div class="fs-16 fw-semibold">{{ M::format('payments_received', $s['payments_received']) }}</div></div>
                <div class="col"><div class="text-muted fs-12">Expenses</div><div class="fs-16 fw-semibold">{{ M::format('expenses', $s['expenses']) }}</div></div>
                <div class="col"><div class="text-muted fs-12">Payments − expenses</div><div class="fs-16 fw-semibold">{{ M::format('net_cash', $s['net_cash']) }}</div></div>
            </div>
        </div>
    @endif

    <p class="text-muted fs-12">How figures are worked out: hen-day = eggs laid ÷ birds alive on laying days; mortality = deaths ÷ birds at the start of the period; eggs per bag = eggs laid ÷ bags fed to laying flocks; feed per bird per day = kg fed × 1000 ÷ bird-days ({{ \App\Support\FarmSettings::bagWeightKg() }} kg bags). Birds alive come from the daily deaths, stolen and culled birds, not from typed totals.</p>
@endsection
