{{-- @include('partials.chart', ['id' => 'x', 'type' => 'line', 'labels' => [...], 'series' => [['name' => .., 'data' => [..]]], 'height' => 300, 'unit' => '%']) --}}
<div id="{{ $id }}" style="min-height: {{ $height ?? 300 }}px"></div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof ApexCharts === 'undefined') { return; }
    var series = @json($series);
    var unit = @json($unit ?? '');
    var fmt = function (v) {
        if (v === null || v === undefined) { return '–'; }
        var n = Number(v);
        var s = Math.abs(n) >= 100 ? n.toLocaleString(undefined, {maximumFractionDigits: 0}) : n.toLocaleString(undefined, {maximumFractionDigits: 1});
        return unit === '₦' ? '₦' + s : (unit === '%' ? s + '%' : s + (unit ? ' ' + unit : ''));
    };
    var opts = window.apexBase(@json($type ?? 'line'), {
        series: series,
        xaxis: { categories: @json(array_values($labels)), labels: { rotate: -45, hideOverlappingLabels: true, trim: true }, tickAmount: Math.min(12, @json(count($labels))) },
        yaxis: { labels: { formatter: fmt }, min: @json($min ?? null) ?? undefined },
        tooltip: { shared: true, intersect: false, y: { formatter: fmt } },
        legend: { show: series.length > 1, position: 'top', horizontalAlign: 'left' },
    });
    opts.chart.height = @json($height ?? 300);
    @if (! empty($stacked)) opts.chart.stacked = true; @endif
    new ApexCharts(document.getElementById(@json($id)), opts).render();
});
</script>
@endpush
