{{-- Live farm numbers strip (values come from the farm records; refreshed by site.js). --}}
@if (count($stats))
    <section class="stats" data-live-url="{{ route('site.live') }}">
        <span class="blob" style="width:420px;height:420px;left:-120px;top:-160px;background:var(--primary)"></span>
        <span class="blob" style="width:320px;height:320px;right:-80px;bottom:-140px;background:var(--accent);animation-delay:-8s"></span>
        <div class="container head reveal">
            <h2>The farm today</h2>
            <span class="live-dot">Live from our farm records</span>
        </div>
        <div class="container grid" data-stagger>
            @foreach ($stats as $st)
                <div class="stat">
                    <i class="{{ $st['icon'] }}"></i>
                    <b data-count="{{ $st['value'] }}" data-suffix="{{ $st['suffix'] }}" data-stat-label="{{ $st['label'] }}">{{ number_format($st['value']) }}{{ $st['suffix'] }}</b>
                    <span>{{ $st['label'] }}</span>
                </div>
            @endforeach
        </div>
    </section>
@endif
