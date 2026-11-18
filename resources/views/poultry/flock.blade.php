@extends('layouts.page')
@php
    use App\Services\PoultryMetrics as M;
    $L = $life;
    $S = $summary;
    $canUpdate = auth()->user()->can('Update flock');
    $isLayer = $flock->flock_type === 'layer' || $L['eggs_laid'] > 0;
@endphp
@section('page')
    @include('partials.page-header', ['icon' => 'ri-home-4-line', 
        'title' => $flock->display_name,
        'subtitle' => collect([
            $flock->type_label, $flock->breed, $flock->house,
            $flock->age_weeks_now ? $flock->age_weeks_now . ' weeks old' : null,
            ucfirst($flock->status),
            $first ? 'records ' . \Carbon\Carbon::parse($first)->format('j M Y') . ' – ' . \Carbon\Carbon::parse($last)->format('j M Y') : 'no records yet',
        ])->filter()->implode(' · '),
        'crumbs' => ['Overview' => route('poultry.overview'), 'Flocks' => route('flocks.index'), $flock->display_name => null],
        'actions' => '<a href="' . route('week-entries.index', $flock->id) . '" class="btn btn-primary btn-sm"><i class="ri-edit-box-line align-bottom me-1"></i>Daily records</a>'
            . ' <a href="' . route('poultry.flocks.export', $flock) . '" class="btn btn-soft-success btn-sm"><i class="ri-file-excel-2-line align-bottom me-1"></i>Excel</a>'
            . ' <a href="' . route('reports.performance', ['flocks' => [$flock->id]]) . '" class="btn btn-soft-primary btn-sm"><i class="ri-file-chart-line align-bottom me-1"></i>Report</a>',
    ])

    @if ($flock->mergedInto)
        <div class="alert alert-secondary">This flock was combined into <a href="{{ route('poultry.flocks.show', $flock->mergedInto) }}" class="alert-link">{{ $flock->mergedInto->display_name }}</a> on {{ $flock->closed_on?->format('j M Y') }}. Its records are kept here.</div>
    @endif
    @if ($flock->mergedFrom->isNotEmpty())
        <div class="alert alert-secondary">Formed from {!! $flock->mergedFrom->map(fn ($s) => '<a class="alert-link" href="' . route('poultry.flocks.show', $s) . '">' . e($s->display_name) . '</a>')->implode(', ') !!}.</div>
    @endif

    {{-- Lifetime figures --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-2">@include('partials.kpi', ['label' => 'Birds now', 'value' => number_format($flock->current_bird_count), 'sub' => 'of ' . number_format($flock->initial_bird_count) . ' at start'])</div>
        <div class="col-6 col-xl-2">@include('partials.kpi', ['label' => 'Deaths to date', 'value' => M::format('deaths', $L['deaths']), 'sub' => M::format('mortality_pct', $L['mortality_pct']) . ' mortality'])</div>
        <div class="col-6 col-xl-2">@include('partials.kpi', ['label' => 'Stolen / culled', 'value' => number_format($L['birds_stolen']) . ' / ' . number_format($L['birds_culled']), 'sub' => 'birds, to date'])</div>
        @if ($isLayer)
            <div class="col-6 col-xl-2">@include('partials.kpi', ['label' => 'Eggs to date', 'value' => M::format('eggs_laid', $L['eggs_laid']), 'sub' => M::format('hen_housed_eggs', $L['hen_housed_eggs']) . ' per bird housed'])</div>
            <div class="col-6 col-xl-2">@include('partials.kpi', ['label' => 'Hen-day (lifetime)', 'value' => M::format('hen_day_pct', $L['hen_day_pct']), 'sub' => M::format('eggs_per_bag', $L['eggs_per_bag']) . ' eggs per bag'])</div>
        @else
            <div class="col-6 col-xl-2">@include('partials.kpi', ['label' => 'Average weight', 'value' => $L['avg_weight_g'] ? number_format($L['avg_weight_g']) . ' g' : '–', 'sub' => 'from sample weighings'])</div>
            <div class="col-6 col-xl-2">@include('partials.kpi', ['label' => 'Feed per bird per day', 'value' => M::format('feed_g_bird_day', $L['feed_g_bird_day']) . ' g', 'sub' => 'lifetime'])</div>
        @endif
        <div class="col-6 col-xl-2">@include('partials.kpi', ['label' => 'Feed to date', 'value' => M::format('feed_bags', $L['feed_bags']) . ' bags', 'sub' => M::format('feed_g_bird_day', $L['feed_g_bird_day']) . ' g/bird/day'])</div>
    </div>

    @include('partials.period-filter')

    <div class="row g-3">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">{{ $isLayer ? 'Hen-day production (weekly)' : 'Birds alive (weekly)' }}</h5></div>
                <div class="card-body">
                    @if ($isLayer)
                        @include('partials.chart', ['id' => 'fHen', 'labels' => array_column($weekly, 'label'), 'series' => [['name' => 'Hen-day %', 'data' => array_column($weekly, 'hen_day_pct')]], 'unit' => '%', 'height' => 260])
                    @else
                        @include('partials.chart', ['id' => 'fHen', 'labels' => array_column($weekly, 'label'), 'series' => [['name' => 'Birds alive', 'data' => array_column($weekly, 'birds_end')]], 'unit' => 'birds', 'height' => 260])
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Deaths (weekly)</h5></div>
                <div class="card-body">
                    @include('partials.chart', ['id' => 'fDeaths', 'type' => 'bar', 'labels' => array_column($weekly, 'label'), 'series' => [['name' => 'Deaths', 'data' => array_column($weekly, 'deaths')]], 'unit' => 'birds', 'height' => 260])
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex"><h5 class="card-title mb-0 flex-grow-1">Month by month</h5><span class="text-muted fs-12">mortality = deaths ÷ birds at the start of the month</span></div>
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-0">
                <thead class="table-light"><tr>
                    <th>Month</th><th class="text-end">Days</th><th class="text-end">Birds at end</th><th class="text-end">Deaths</th><th class="text-end">Mortality</th>
                    @if ($isLayer)<th class="text-end">Eggs laid</th><th class="text-end">Hen-day</th><th class="text-end">Eggs / bag</th><th class="text-end">Broken + bad</th>@endif
                    <th class="text-end">Feed (bags)</th><th class="text-end">g / bird / day</th><th class="text-end">Days on drugs</th>
                </tr></thead>
                <tbody>
                    @foreach (array_reverse($monthly) as $m)
                        <tr>
                            <td>{{ $m['label'] }}</td>
                            <td class="text-end">{{ $m['days_reported'] }}</td>
                            <td class="text-end">{{ number_format($m['birds_end']) }}</td>
                            <td class="text-end">{{ number_format($m['deaths']) }}</td>
                            <td class="text-end">{{ M::format('mortality_pct', $m['mortality_pct']) }}</td>
                            @if ($isLayer)
                                <td class="text-end">{{ number_format($m['eggs_laid']) }}</td>
                                <td class="text-end">{{ M::format('hen_day_pct', $m['hen_day_pct']) }}</td>
                                <td class="text-end">{{ M::format('eggs_per_bag', $m['eggs_per_bag']) }}</td>
                                <td class="text-end">{{ M::format('broken_pct', $m['broken_pct']) }}</td>
                            @endif
                            <td class="text-end">{{ M::format('feed_bags', $m['feed_bags']) }}</td>
                            <td class="text-end">{{ M::format('feed_g_bird_day', $m['feed_g_bird_day']) }}</td>
                            <td class="text-end">{{ $m['drug_days'] }}</td>
                        </tr>
                    @endforeach
                    <tr class="fw-semibold table-light">
                        <td>Selected period</td><td class="text-end">{{ $S['days_reported'] }}</td><td class="text-end">{{ number_format($S['birds_end']) }}</td>
                        <td class="text-end">{{ number_format($S['deaths']) }}</td><td class="text-end">{{ M::format('mortality_pct', $S['mortality_pct']) }}</td>
                        @if ($isLayer)
                            <td class="text-end">{{ number_format($S['eggs_laid']) }}</td><td class="text-end">{{ M::format('hen_day_pct', $S['hen_day_pct']) }}</td>
                            <td class="text-end">{{ M::format('eggs_per_bag', $S['eggs_per_bag']) }}</td><td class="text-end">{{ M::format('broken_pct', $S['broken_pct']) }}</td>
                        @endif
                        <td class="text-end">{{ M::format('feed_bags', $S['feed_bags']) }}</td><td class="text-end">{{ M::format('feed_g_bird_day', $S['feed_g_bird_day']) }}</td><td class="text-end">{{ $S['drug_days'] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="card">
                <div class="card-header d-flex align-items-center"><h5 class="card-title mb-0 flex-grow-1">Latest records</h5>
                    <a href="{{ route('week-entries.index', $flock->id) }}" class="btn btn-sm btn-soft-secondary">All weeks</a></div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>Date</th><th>Week</th><th class="text-end">Deaths</th><th class="text-end">Birds</th>@if ($isLayer)<th class="text-end">Laid</th><th class="text-end">Hen-day</th><th class="text-end">Sold</th>@endif<th class="text-end">Feed</th><th>Medication / notes</th></tr></thead>
                        <tbody>
                            @forelse ($recent as $e)
                                <tr>
                                    <td class="text-nowrap"><a href="{{ route('daily-entries.index', $e->week_entry_id) }}">{{ $e->entry_date->format('D j M') }}</a></td>
                                    <td>{{ $e->weekEntry?->week_name }}</td>
                                    <td class="text-end">{{ $e->daily_mortality }}@if ($e->birds_stolen)<span class="text-warning" title="stolen / missing"> +{{ $e->birds_stolen }}</span>@endif</td>
                                    <td class="text-end">{{ number_format($e->current_birds) }}</td>
                                    @if ($isLayer)
                                        <td class="text-end">{{ number_format($e->eggs_produced) }}</td>
                                        <td class="text-end">{{ $e->hen_day !== null ? $e->hen_day . '%' : '–' }}</td>
                                        <td class="text-end">{{ number_format($e->eggs_sold) }}</td>
                                    @endif
                                    <td class="text-end">{{ rtrim(rtrim(number_format((float) $e->daily_feeds, 2), '0'), '.') }}</td>
                                    <td class="text-muted fs-12">{{ collect([$e->has_drugs ? $e->drugs : null, $e->notes])->filter()->implode(' · ') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-muted py-3">No records yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card" id="issues">
                <div class="card-header d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">Data checks <span class="badge bg-light text-muted">{{ count($issues) }}</span></h5>
                    @if ($canUpdate)
                        <form method="POST" action="{{ route('poultry.flocks.recalculate', $flock) }}">@csrf
                            <button class="btn btn-sm btn-soft-secondary" title="Recalculate running totals from the daily records"><i class="ri-refresh-line align-bottom"></i> Recalculate</button>
                        </form>
                    @endif
                </div>
                <div class="card-body p-0" style="max-height: 460px; overflow-y: auto">
                    <p class="text-muted fs-12 px-3 pt-2 mb-1">Where the farm's own counts disagree with the daily figures, or days are missing. Fix the day's record, or note the reason.</p>
                    @forelse ($issues as $i)
                        <div class="d-flex gap-2 px-3 py-2 border-bottom">
                            <i class="{{ $i['severity'] === 'warning' ? 'ri-alert-line text-warning' : 'ri-information-line text-info' }} mt-1" aria-hidden="true"></i>
                            <div class="fs-13">
                                @if ($i['date'])<span class="fw-medium">{{ \Carbon\Carbon::parse($i['date'])->format('j M Y') }}</span> · @endif{{ $i['message'] }}
                            </div>
                        </div>
                    @empty
                        <p class="px-3 py-2 mb-0"><i class="ri-checkbox-circle-line text-success me-1"></i>The records add up.</p>
                    @endforelse
                </div>
            </div>
            @can('Merge flock')
                @if ($flock->status === 'active')
                    <a href="{{ route('poultry.merge', ['sources' => [$flock->id]]) }}" class="btn btn-light w-100 mb-3"><i class="ri-git-merge-line align-bottom me-1"></i>Combine this flock with another</a>
                @endif
            @endcan
        </div>
    </div>
@endsection
