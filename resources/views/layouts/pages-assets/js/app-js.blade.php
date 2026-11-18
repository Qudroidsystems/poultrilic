<!-- JAVASCRIPT (poultry, livestock, imports and reports pages) -->
<script src="{{ asset('theme/layouts/assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('theme/layouts/assets/libs/simplebar/simplebar.min.js') }}"></script>
<script src="{{ asset('theme/layouts/assets/js/plugins.js') }}"></script>
<script src="{{ asset('theme/layouts/assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{ asset('theme/layouts/assets/libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
    // Categorical colours, assigned in this fixed order (validated for colour-blind separation).
    window.SERIES = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#6250d6', '#e34948'];
    window.apexBase = function (type, extra) {
        return Object.assign({
            chart: { type: type, height: 300, toolbar: { show: false }, fontFamily: 'Poppins, sans-serif', zoom: { enabled: false } },
            colors: window.SERIES,
            stroke: { width: type === 'line' ? 2 : 0, curve: 'straight' },
            markers: { size: type === 'line' ? 3 : 0, strokeWidth: 0, hover: { size: 6 } },
            dataLabels: { enabled: false },
            grid: { borderColor: 'rgba(120,120,120,.15)', strokeDashArray: 3 },
            legend: { position: 'top', horizontalAlign: 'left' },
            plotOptions: { bar: { borderRadius: 4, borderRadiusApplication: 'end', columnWidth: '60%' } },
            tooltip: { shared: true, intersect: false },
            noData: { text: 'No records in this period' },
        }, extra || {});
    };
    // Confirm buttons/forms marked data-confirm.
    document.addEventListener('submit', function (e) {
        var msg = e.target.getAttribute('data-confirm');
        if (msg && !confirm(msg)) { e.preventDefault(); }
    });
</script>
<script src="{{ asset('theme/layouts/assets/js/app.js') }}"></script>
