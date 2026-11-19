{{-- Live from the farm: eggs collected per day (bars grow in), laying-rate ring and egg stock. Real data only. --}}
@php
    $W = \App\Services\Website\WebsiteContent::class;
    $showStats = ($site['show_stats'] ?? '1') === '1';
    $allowed = array_filter(explode(',', (string) ($site['stats_items'] ?? 'birds,eggs_month,laying_rate,units,customers,years')));
    $trend = $showStats && in_array('eggs_month', $allowed, true) ? $W::eggTrend(14) : [];
    $max = max(1, collect($trend)->max('eggs') ?? 1);
    $avg = count($trend) ? (int) round(collect($trend)->avg('eggs')) : 0;
    $rate = $showStats && in_array('laying_rate', $allowed, true) ? (collect($W::stats())->firstWhere('suffix', '%')['value'] ?? null) : null;
    $stock = $showStats && in_array('egg_stock', $allowed, true) ? $W::eggStock() : 0;
@endphp
@if (count($trend) >= 3)
    <section class="section trend-sec">
        <div class="container trend-wrap">
            <div class="reveal from-left">
                <span class="live-dot dark">Live from our farm records</span>
                <h2 style="margin-top:12px">{{ $title ?? 'This fortnight on the farm' }}</h2>
                <p class="lead">Every bar is a real day of egg collection across our flocks — straight from the records our team keeps every day.</p>
                <div class="trend-facts" data-stagger>
                    <div><b data-count="{{ $avg }}">{{ number_format($avg) }}</b><span>eggs a day on average</span></div>
                    @if ($rate)
                        <div class="ring-box">
                            <svg class="ring" viewBox="0 0 120 120" style="--v: {{ min(100, $rate) }}"><circle cx="60" cy="60" r="52" class="bg"/><circle cx="60" cy="60" r="52" class="fg" pathLength="100"/></svg>
                            <b class="ring-n">{{ $rate }}%</b><span>laying rate</span>
                        </div>
                    @endif
                    @if ($stock > 0)<div><b data-count="{{ intdiv($stock, 30) }}">{{ number_format(intdiv($stock, 30)) }}</b><span>crates ready to sell</span></div>@endif
                </div>
            </div>
            <div class="chart-card reveal from-right d2" aria-label="Eggs collected per day">
                <div class="bars" style="--max: {{ $max }}">
                    @foreach ($trend as $i => $d)
                        <div class="bar" style="--h: {{ round($d['eggs'] / $max * 100, 1) }}%; --i: {{ $i }}" title="{{ $d['date'] }}: {{ number_format($d['eggs']) }} eggs">
                            <span class="tip">{{ number_format($d['eggs']) }}</span>
                            <i></i>
                            <small>{{ $d['date'] }}</small>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif
