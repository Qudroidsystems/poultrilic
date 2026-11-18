@php
    use App\Services\PoultryMetrics as M;
    use App\Services\LivestockMetrics as LM;
    $s = $summary;
    $sevLabel = ['danger' => 'ACT NOW', 'warning' => 'CHECK', 'info' => 'NOTE'];
    $sevColor = ['danger' => '#c0392b', 'warning' => '#b7791f', 'info' => '#2a78d6'];
    $colors = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#6250d6', '#e34948'];
    // Bar width helper for CSS bar charts (dompdf has no JavaScript).
    $bar = function ($v, $max) { return $max > 0 && $v !== null ? max(1, round($v / $max * 100)) : 0; };
@endphp
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Farm performance report</title>
<style>
    @page { margin: 22mm 14mm 18mm 14mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1f2937; }
    h1 { font-size: 20px; margin: 0 0 2px; color: #405189; }
    h2 { font-size: 13px; color: #405189; border-bottom: 1.5px solid #405189; padding-bottom: 3px; margin: 18px 0 8px; page-break-after: avoid; }
    tr { page-break-inside: avoid; }
    h3 { font-size: 11px; margin: 10px 0 4px; }
    .muted { color: #6b7280; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #eef1f8; text-align: left; font-weight: bold; }
    th, td { padding: 3px 5px; border-bottom: 0.5px solid #d9dde6; }
    .r { text-align: right; }
    .kpis td { border: none; padding: 6px; width: 16.6%; vertical-align: top; }
    .kpi { border: 0.6px solid #d9dde6; border-radius: 4px; padding: 6px 8px; }
    .kpi .l { font-size: 7.5px; text-transform: uppercase; color: #6b7280; }
    .kpi .v { font-size: 15px; font-weight: bold; margin-top: 2px; }
    .bar { height: 7px; background: #2a78d6; border-radius: 0 2px 2px 0; }
    .obs { border-left: 3px solid #999; padding: 4px 8px; margin-bottom: 5px; background: #fafbfc; }
    .tag { font-size: 7px; font-weight: bold; letter-spacing: .5px; }
    .footer { position: fixed; bottom: -10mm; left: 0; right: 0; font-size: 7.5px; color: #9ca3af; }
    .pb { page-break-before: always; }
    .key span { display: inline-block; width: 8px; height: 8px; margin: 0 3px 0 8px; }
</style></head>
<body>
<div class="footer">{{ $farm ?: 'Poultrilic' }} · Farm performance report · generated {{ $generated->format('j M Y H:i') }}</div>

<h1>Farm performance report</h1>
<div class="muted">{{ $farm }} · {{ $from ? \Carbon\Carbon::parse($from)->format('j M Y') : 'First record' }} – {{ $to ? \Carbon\Carbon::parse($to)->format('j M Y') : 'latest record' }} · {{ count($byFlock) }} flock(s)</div>

<h2>1. At a glance</h2>
<table class="kpis"><tr>
    <td><div class="kpi"><div class="l">Days of records</div><div class="v">{{ number_format($s['days_reported']) }}</div></div></td>
    <td><div class="kpi"><div class="l">Eggs laid</div><div class="v">{{ number_format($s['eggs_laid']) }}</div><div class="muted">{{ number_format($s['crates_laid'], 1) }} crates</div></div></td>
    <td><div class="kpi"><div class="l">Hen-day</div><div class="v">{{ M::format('hen_day_pct', $s['hen_day_pct']) }}</div></div></td>
    <td><div class="kpi"><div class="l">Deaths</div><div class="v">{{ number_format($s['deaths']) }}</div><div class="muted">{{ M::format('mortality_pct', $s['mortality_pct']) }}</div></div></td>
    <td><div class="kpi"><div class="l">Stolen / missing</div><div class="v">{{ number_format($s['birds_stolen']) }}</div></div></td>
    <td><div class="kpi"><div class="l">Birds now</div><div class="v">{{ number_format($birdsNow) }}</div></div></td>
</tr></table>

<h2>2. What the records say</h2>
@forelse ($observations as [$sev, $title, $detail])
    <div class="obs" style="border-left-color: {{ $sevColor[$sev] }}"><span class="tag" style="color: {{ $sevColor[$sev] }}">{{ $sevLabel[$sev] }}</span> <strong>{{ $title }}</strong><br><span class="muted">{{ $detail }}</span></div>
@empty
    <p class="muted">Nothing unusual found.</p>
@endforelse

<h2>3. Flocks</h2>
<table>
    <tr><th>Flock</th><th class="r">Birds start</th><th class="r">Deaths</th><th class="r">Stolen</th><th class="r">Birds end</th><th class="r">Mortality</th><th class="r">Eggs laid</th><th class="r">Hen-day</th><th class="r">Eggs/bag</th><th class="r">g/bird/day</th></tr>
    @foreach ($byFlock as $b)
        @php($fs = $b['summary'])
        <tr><td>{{ $b['flock']->display_name }}@if ($b['flock']->merged_into_id)<br><span class="muted">combined into {{ optional($b['flock']->mergedInto)->display_name }}</span>@endif</td>
            <td class="r">{{ number_format($fs['birds_start']) }}</td><td class="r">{{ number_format($fs['deaths']) }}</td><td class="r">{{ number_format($fs['birds_stolen']) }}</td>
            <td class="r">{{ number_format($fs['birds_end']) }}</td><td class="r">{{ M::format('mortality_pct', $fs['mortality_pct']) }}</td><td class="r">{{ number_format($fs['eggs_laid']) }}</td>
            <td class="r">{{ M::format('hen_day_pct', $fs['hen_day_pct']) }}</td><td class="r">{{ M::format('eggs_per_bag', $fs['eggs_per_bag']) }}</td><td class="r">{{ M::format('feed_g_bird_day', $fs['feed_g_bird_day']) }}</td></tr>
    @endforeach
</table>

<h2>4. Eggs laid by month</h2>
@php($maxEggs = collect($monthlyFarm)->max('eggs_laid') ?: 0)
<table>
    <tr><th style="width: 70px">Month</th><th class="r" style="width: 60px">Eggs</th><th class="r" style="width: 50px">Hen-day</th><th class="r" style="width: 50px">Eggs/bag</th><th class="r" style="width: 45px">Deaths</th><th></th></tr>
    @foreach ($monthlyFarm as $m)
        <tr><td>{{ $m['label'] }}</td><td class="r">{{ number_format($m['eggs_laid']) }}</td><td class="r">{{ M::format('hen_day_pct', $m['hen_day_pct']) }}</td><td class="r">{{ M::format('eggs_per_bag', $m['eggs_per_bag']) }}</td><td class="r">{{ number_format($m['deaths']) }}</td>
            <td><div class="bar" style="width: {{ $bar($m['eggs_laid'], $maxEggs) }}%"></div></td></tr>
    @endforeach
</table>

@foreach ([['Hen-day production by flock, monthly (%)', 'hen_day_pct', 'pct1'], ['Mortality by flock, monthly (%)', 'mortality_pct', 'pct2'], ['Eggs per bag of feed by flock, monthly', 'eggs_per_bag', 'dec0']] as $i => [$title, $metric])
    <h2>{{ 5 + $i }}. {{ $title }}</h2>
    <table>
        <tr><th>Month</th>@foreach ($byFlock as $b)<th class="r">{{ $b['flock']->display_name }}</th>@endforeach</tr>
        @foreach ($months as $key => $label)
            <tr><td>{{ $label }}</td>
                @foreach ($byFlock as $b)<td class="r">{{ isset($b['monthly'][$key]) ? M::format($metric, $b['monthly'][$key][$metric]) : '' }}</td>@endforeach</tr>
        @endforeach
    </table>
@endforeach

<h2 class="pb">8. Data checks</h2>
<p class="muted">Where the farm's own totals disagree with the daily figures, days are missing or reports arrived late.</p>
@foreach ($byFlock as $b)
    @if ($b['issues']->isNotEmpty())
        <h3>{{ $b['flock']->display_name }} ({{ $b['issues']->count() }})</h3>
        <table>
            @foreach ($b['issues']->take(15) as $i)
                <tr><td style="width: 70px">{{ $i['date'] ? \Carbon\Carbon::parse($i['date'])->format('j M Y') : '' }}</td><td>{{ $i['message'] }}</td></tr>
            @endforeach
            @if ($b['issues']->count() > 15)<tr><td></td><td class="muted">… and {{ $b['issues']->count() - 15 }} more (see the flock page)</td></tr>@endif
        </table>
    @endif
@endforeach

@foreach ($livestock as $n => $ls)
    @php($t = $ls['type'])
    @php($keys = collect($ls['catalogue'])->keys()->filter(fn ($k) => str_starts_with($k, 'field:'))->take(6)->values())
    <h2>{{ 9 + $n }}. {{ $t->name }}</h2>
    <table>
        <tr><th>{{ $t->unit_label }}</th><th class="r">At start</th><th class="r">At end</th>@foreach ($keys as $k)<th class="r">{{ $ls['catalogue'][$k][0] }}</th>@endforeach</tr>
        @foreach ($ls['units'] as $u)
            <tr><td>{{ $u['label'] }}</td><td class="r">{{ number_format($u['head_start']) }}</td><td class="r">{{ number_format($u['head_end']) }}</td>@foreach ($keys as $k)<td class="r">{{ LM::format($ls['catalogue'][$k][2], $u[$k] ?? null) }}</td>@endforeach</tr>
        @endforeach
    </table>
@endforeach

@if ($s['sales_invoiced'] || $s['payments_received'] || $s['expenses'])
    <h2>Money in the period</h2>
    <table><tr><th>Sales invoiced</th><th>Payments received</th><th>Expenses</th><th>Payments − expenses</th></tr>
        <tr><td>{{ M::format('sales_invoiced', $s['sales_invoiced']) }}</td><td>{{ M::format('payments_received', $s['payments_received']) }}</td><td>{{ M::format('expenses', $s['expenses']) }}</td><td>{{ M::format('net_cash', $s['net_cash']) }}</td></tr></table>
@endif

<h2>How the figures are worked out</h2>
<p class="muted">Hen-day = eggs laid ÷ birds alive on laying days. Mortality = deaths ÷ birds at the start of the period. Eggs per bag = eggs laid ÷ bags fed to laying flocks.
Feed per bird per day = kg fed × 1000 ÷ bird-days ({{ \App\Support\FarmSettings::bagWeightKg() }} kg bags). Birds alive come from the daily deaths, stolen and culled birds, not from typed totals; typed totals are only used for the data checks.</p>
</body></html>
