@extends('layouts.master')
@section('content')

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">{{ $pagetitle }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboards</a></li>
                                <li class="breadcrumb-item active">Poultry Analytics</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End page title -->

            <!-- Filters -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="flockFilter" class="form-label">Filter by Flock</label>
                    <select id="flockFilter" name="flock_id" class="form-select">
                        <option value="">All Flocks</option>
                        @foreach ($flocks as $flock)
                            <option value="{{ $flock->id }}" {{ $flockId == $flock->id ? 'selected' : '' }}>
                                Flock {{ $flock->id }} ({{ $flock->initial_bird_count }} birds)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="dateRangePicker" class="form-label">Date Range</label>
                    <input type="text" class="form-control" id="dateRangePicker" data-provider="flatpickr" data-range-date="true" data-date-format="Y-m-d" value="{{ $startDate->format('Y-m-d') }} to {{ $endDate->format('Y-m-d') }}">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-download me-1"></i> Export
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                            <li><a class="dropdown-item" href="{{ route('dashboard.export', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d'), 'flock_id' => $flockId, 'format' => 'csv']) }}">Export to CSV</a></li>
                            <li><a class="dropdown-item" href="{{ route('dashboard.export', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d'), 'flock_id' => $flockId, 'format' => 'pdf']) }}">Export to PDF</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Key Metrics -->
            <div class="row">
                <div class="col-xxl-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="fs-md text-muted mb-4">Total Birds</p>
                                    <h3 class="mb-0 mt-auto"><span class="counter-value" data-target="{{ $totalBirds }}">{{ number_format($totalBirds, 0) }}</span></h3>
                                    <small class="text-muted">Initial count</small>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded fs-3">
                                            <i class="bi bi-egg-fried"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Debug section --}}
@if(app()->environment('local'))
    <div class="debug-section">
        <h4>Debug: Flock Analysis</h4>
        <pre>{{ print_r($flockAnalysis, true) }}</pre>
    </div>
@endif
                <div class="col-xxl-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="fs-md text-muted mb-4">Current Birds</p>
                                    <h3 class="mb-0 mt-auto"><span class="counter-value" data-target="{{ $currentBirds }}">{{ number_format($currentBirds, 0) }}</span></h3>
                                    <small class="text-muted">Latest count</small>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm">
                                        <span class="avatar-title bg-success-subtle text-success rounded fs-3">
                                            <i class="bi bi-people"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- <div class="col-xxl-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="fs-md text-muted mb-4">Total Egg Production</p>
                                    <h3 class="mb-0 mt-auto">
                                        <span class="counter-value" data-target="{{ $totalEggProductionCrates }}">
                                            {{ number_format($totalEggProductionCrates, 0) }}
                                        </span> Cr
                                    </h3>
                                    <small class="text-muted">{{ number_format($totalEggProduction, 0) }} eggs</small>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm">
                                        <span class="avatar-title bg-info-subtle text-info rounded fs-3">
                                            <i class="bi bi-egg"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> --}}
                <div class="col-xxl-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="fs-md text-muted mb-4">Bird Mortality</p>
                                    <h3 class="mb-0 mt-auto"><span class="counter-value" data-target="{{ $totalMortality }}">{{ number_format($totalMortality, 0) }}</span></h3>
                                    <small class="text-muted">Total losses</small>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm">
                                        <span class="avatar-title bg-danger-subtle text-danger rounded fs-3">
                                            <i class="bi bi-activity"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Egg Production Card -->
            <div class="col-xxl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="flex-grow-1">
                                <p class="fs-md text-muted mb-4">Total Egg Production</p>
                                <h3 class="mb-0 mt-auto">
                                    <span class="counter-value" data-target="{{ $totalEggProductionCrates }}">
                                        {{ number_format($totalEggProductionCrates, 0) }}
                                    </span> Cr
                                    <span class="counter-value" data-target="{{ $totalEggProductionPieces }}">
                                        {{ $totalEggProductionPieces }}
                                    </span> Pc
                                </h3>
                                <small class="text-muted">{{ number_format($totalEggProductionTotalPieces, 0) }} total eggs</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feed Consumption Card -->
            <div class="col-xxl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="flex-grow-1">
                                <p class="fs-md text-muted mb-4">Feed Consumed</p>
                                <h3 class="mb-0 mt-auto">
                                    <span class="counter-value" data-target="{{ $totalFeedBags }}">
                                        {{ number_format($totalFeedBags, 1) }}
                                    </span> Bags
                                </h3>
                                <small class="text-muted">{{ number_format($totalFeedKg, 1) }} kg total</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Eggs Sold Card -->
            <div class="col-xxl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="flex-grow-1">
                                <p class="fs-md text-muted mb-4">Eggs Sold</p>
                                <h3 class="mb-0 mt-auto">
                                    <span class="counter-value" data-target="{{ $totalEggsSoldCrates }}">
                                        {{ number_format($totalEggsSoldCrates, 0) }}
                                    </span> Cr
                                    <span class="counter-value" data-target="{{ $totalEggsSoldPieces }}">
                                        {{ $totalEggsSoldPieces }}
                                    </span> Pc
                                </h3>
                                <small class="text-muted">{{ number_format($totalEggsSoldTotalPieces, 0) }} total eggs</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Second Row Metrics -->
            <div class="row">
                <div class="col-xxl-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="fs-md text-muted mb-4">Feed Consumed</p>
                                    <h3 class="mb-0 mt-auto"><span class="counter-value" data-target="{{ $totalFeedConsumed }}">{{ number_format($totalFeedConsumed, 1) }}</span> kg</h3>
                                    <small class="text-muted">Total consumption</small>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm">
                                        <span class="avatar-title bg-warning-subtle text-warning rounded fs-3">
                                            <i class="bi bi-basket"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="fs-md text-muted mb-4">Eggs Sold</p>
                                    <h3 class="mb-0 mt-auto"><span class="counter-value" data-target="{{ $totalEggsSold }}">{{ number_format($totalEggsSold, 0) }}</span></h3>
                                    <small class="text-muted">Total sales</small>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm">
                                        <span class="avatar-title bg-success-subtle text-success rounded fs-3">
                                            <i class="bi bi-cash-coin"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="fs-md text-muted mb-4">Production Rate</p>
                                    <h3 class="mb-0 mt-auto"><span class="counter-value" data-target="{{ $avgProductionRate }}">{{ number_format($avgProductionRate, 1) }}</span>%</h3>
                                    <small class="text-muted">Average daily rate</small>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm">
                                        <span class="avatar-title bg-info-subtle text-info rounded fs-3">
                                            <i class="bi bi-graph-up"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="fs-md text-muted mb-4">Egg Mortality</p>
                                    <h3 class="mb-0 mt-auto"><span class="counter-value" data-target="{{ $totalEggMortality }}">{{ number_format($totalEggMortality, 0) }}</span></h3>
                                    <small class="text-muted">Broken eggs</small>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm">
                                        <span class="avatar-title bg-danger-subtle text-danger rounded fs-3">
                                            <i class="bi bi-x-circle"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Metrics -->
            <div class="row">
                <div class="col-xxl-4 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="fs-md text-muted mb-4">Drug Usage</p>
                                    <h3 class="mb-0 mt-auto"><span class="counter-value" data-target="{{ $totalDrugUsage }}">{{ number_format($totalDrugUsage, 0) }}</span></h3>
                                    <small class="text-muted">Treatment days</small>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded fs-3">
                                            <i class="bi bi-capsule"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-4 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="fs-md text-muted mb-4">Total Revenue</p>
                                    <h3 class="mb-0 mt-auto">$<span class="counter-value" data-target="{{ $totalRevenue }}">{{ number_format($totalRevenue, 2) }}</span></h3>
                                    <small class="text-muted">From egg sales</small>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm">
                                        <span class="avatar-title bg-success-subtle text-success rounded fs-3">
                                            <i class="bi bi-currency-dollar"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-4 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="fs-md text-muted mb-4">Net Income</p>
                                    <h3 class="mb-0 mt-auto {{ $netIncome < 0 ? 'text-danger' : 'text-success' }}">
                                        $<span class="counter-value" data-target="{{ abs($netIncome) }}">{{ number_format($netIncome, 2) }}</span>
                                    </h3>
                                    <small class="text-muted">After expenses</small>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm">
                                        <span class="avatar-title {{ $netIncome < 0 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }} rounded fs-3">
                                            <i class="bi bi-graph-up-arrow"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="row">
                <div class="col-xxl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Feed Consumption (Last 4 Weeks)</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="feedConsumptionChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Drug Usage (Treatment Days)</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="drugUsageChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xxl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Egg Production vs. Sold</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="eggProductionVsSoldChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Production Rate & Egg Mortality</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="productionRateAndEggMortalityChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Analysis Section -->
            <div class="row">
                <div class="col-xxl-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Financial Breakdown</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title">Income</h6>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Egg Sales:</span>
                                                <strong class="text-success">${{ number_format($totalRevenue, 2) }}</strong>
                                            </div>
                                            <hr>
                                            <div class="d-flex justify-content-between">
                                                <span>Total Income:</span>
                                                <strong class="text-success">${{ number_format($totalRevenue, 2) }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title">Expenses</h6>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Feed Cost:</span>
                                                <strong class="text-danger">${{ number_format($feedCost, 2) }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Drug Cost:</span>
                                                <strong class="text-danger">${{ number_format($drugCost, 2) }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Labor Cost:</span>
                                                <strong class="text-danger">${{ number_format($laborCost, 2) }}</strong>
                                            </div>
                                            <hr>
                                            <div class="d-flex justify-content-between">
                                                <span>Total Expenses:</span>
                                                <strong class="text-danger">${{ number_format($operationalExpenses, 2) }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="card {{ $netIncome < 0 ? 'bg-danger-subtle' : 'bg-success-subtle' }}">
                                        <div class="card-body text-center">
                                            <h4 class="{{ $netIncome < 0 ? 'text-danger' : 'text-success' }}">
                                                Net Income: ${{ number_format($netIncome, 2) }}
                                            </h4>
                                            @if($netIncome < 0)
                                                <p class="text-danger mb-0">Operating at a loss</p>
                                            @else
                                                <p class="text-success mb-0">Profitable operation</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Flock Capital Analysis</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Capital Investment:</strong><br>
                                <span class="text-primary">${{ number_format($capitalInvestment, 2) }}</span>
                                <small class="text-muted d-block">{{ number_format($totalBirds, 0) }} birds × $2.00</small>
                            </div>
                            <div class="mb-3">
                                <strong>Operational Expenses:</strong><br>
                                <span class="text-danger">${{ number_format($operationalExpenses, 2) }}</span>
                            </div>
                            <div class="mb-3">
                                <strong>Net Income:</strong><br>
                                <span class="{{ $netIncome < 0 ? 'text-danger' : 'text-success' }}">
                                    ${{ number_format($netIncome, 2) }}
                                </span>
                            </div>
                            <div class="mb-3">
                                <strong>Capital Value:</strong><br>
                                <span class="text-info">${{ number_format($capitalValue, 2) }}</span>
                                <small class="text-muted d-block">Based on income approach (10% cap rate)</small>
                            </div>
                            <canvas id="flockCapitalChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Indicators -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Key Performance Indicators</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 text-center">
                                    <div class="mb-3">
                                        <h3 class="text-primary">{{ number_format($avgProductionRate, 1) }}%</h3>
                                        <p class="text-muted mb-0">Production Rate</p>
                                    </div>
                                </div>
                                <div class="col-md-3 text-center">
                                    <div class="mb-3">
                                        <h3 class="text-info">{{ $totalBirds > 0 ? number_format(($totalMortality / $totalBirds) * 100, 2) : 0 }}%</h3>
                                        <p class="text-muted mb-0">Mortality Rate</p>
                                    </div>
                                </div>
                                <div class="col-md-3 text-center">
                                    <div class="mb-3">
                                        <h3 class="text-success">${{ number_format($totalRevenue / max($currentBirds, 1), 2) }}</h3>
                                        <p class="text-muted mb-0">Revenue per Bird</p>
                                    </div>
                                </div>
                                <div class="col-md-3 text-center">
                                    <div class="mb-3">
                                        <h3 class="text-warning">{{ $currentBirds > 0 ? number_format($totalFeedConsumed / $currentBirds, 2) : 0 }}kg</h3>
                                        <p class="text-muted mb-0">Feed per Bird</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/countup.js/2.8.0/countUp.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Debug chart data
        console.log('Weeks:', {!! json_encode($weeks) !!});
        console.log('Feed Data:', {!! json_encode(array_values($feedChartData)) !!});
        console.log('Drug Data:', {!! json_encode(array_values($drugChartData)) !!});
        console.log('Egg Production Data:', {!! json_encode(array_values($eggProductionChartData)) !!});
        console.log('Egg Sold Data:', {!! json_encode(array_values($eggSoldChartData)) !!});
        console.log('Production Rate Data:', {!! json_encode(array_values($productionRateChartData)) !!});
        console.log('Egg Mortality Data:', {!! json_encode(array_values($eggMortalityChartData)) !!});

        // Initialize Flatpickr
        flatpickr('#dateRangePicker', {
            mode: 'range',
            dateFormat: 'Y-m-d',
            defaultDate: ['{{ $startDate->format('Y-m-d') }}', '{{ $endDate->format('Y-m-d') }}'],
            onChange: function(selectedDates) {
                if (selectedDates.length === 2) {
                    const startDate = selectedDates[0].toISOString().split('T')[0];
                    const endDate = selectedDates[1].toISOString().split('T')[0];
                    const flockId = document.getElementById('flockFilter').value;
                    window.location.href = '{{ route('dashboard') }}?start_date=' + startDate + '&end_date=' + endDate + '&flock_id=' + flockId;
                }
            }
        });

        // Flock Filter Change
        document.getElementById('flockFilter').addEventListener('change', function() {
            const startDate = '{{ $startDate->format('Y-m-d') }}';
            const endDate = '{{ $endDate->format('Y-m-d') }}';
            const flockId = this.value;
            window.location.href = '{{ route('dashboard') }}?start_date=' + startDate + '&end_date=' + endDate + '&flock_id=' + flockId;
        });

        // Initialize Counter Animations
        document.querySelectorAll('.counter-value').forEach(function(element) {
            try {
                const targetValue = parseFloat(element.getAttribute('data-target'));
                const countUp = new CountUp(element, targetValue, {
                    duration: 2,
                    separator: ',',
                    decimal: '.',
                    decimalPlaces: element.textContent.includes('$') ? 2 : 0
                });
                if (!countUp.error) {
                    countUp.start();
                } else {
                    console.error(countUp.error);
                }
            } catch (error) {
                console.error('CountUp Error:', error);
            }
        });

        // Chart configuration
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        };

        // Initialize Feed Consumption Chart
        try {
            new Chart(document.getElementById('feedConsumptionChart'), {
                type: 'line',
                data: {
                    labels: {!! json_encode($weeks->map(function($week) {
                        return 'Week ' . substr($week, -2);
                    })) !!},
                    datasets: [{
                        label: 'Feed Consumption (kg)',
                        data: {!! json_encode(array_values($feedChartData)) !!},
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    ...chartOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Kilograms (kg)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Week'
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Feed Consumption Chart Error:', error);
        }

        // Initialize Drug Usage Chart
        try {
            new Chart(document.getElementById('drugUsageChart'), {
                type: 'bar',
                data: {
                    labels: {!! json_encode($weeks->map(function($week) {
                        return 'Week ' . substr($week, -2);
                    })) !!},
                    datasets: [{
                        label: 'Treatment Days',
                        data: {!! json_encode(array_values($drugChartData)) !!},
                        backgroundColor: '#dc3545',
                        borderColor: '#dc3545',
                        borderWidth: 1
                    }]
                },
                options: {
                    ...chartOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Days'
                            },
                            ticks: {
                                stepSize: 1
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Week'
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Drug Usage Chart Error:', error);
        }

        // Initialize Egg Production vs. Sold Chart
        try {
            new Chart(document.getElementById('eggProductionVsSoldChart'), {
                type: 'bar',
                data: {
                    labels: {!! json_encode($weeks->map(function($week) {
                        return 'Week ' . substr($week, -2);
                    })) !!},
                    datasets: [
                        {
                            label: 'Eggs Produced',
                            data: {!! json_encode(array_values($eggProductionChartData)) !!},
                            backgroundColor: '#28a745',
                            borderColor: '#28a745',
                            borderWidth: 1
                        },
                        {
                            label: 'Eggs Sold',
                            data: {!! json_encode(array_values($eggSoldChartData)) !!},
                            backgroundColor: '#ffc107',
                            borderColor: '#ffc107',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    ...chartOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Eggs'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Week'
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Egg Production vs. Sold Chart Error:', error);
        }

        // Initialize Production Rate & Egg Mortality Chart
        try {
            new Chart(document.getElementById('productionRateAndEggMortalityChart'), {
                type: 'line',
                data: {
                    labels: {!! json_encode($weeks->map(function($week) {
                        return 'Week ' . substr($week, -2);
                    })) !!},
                    datasets: [
                        {
                            label: 'Production Rate (%)',
                            data: {!! json_encode(array_values($productionRateChartData)) !!},
                            borderColor: '#17a2b8',
                            backgroundColor: 'rgba(23, 162, 184, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Egg Mortality',
                            data: {!! json_encode(array_values($eggMortalityChartData)) !!},
                            borderColor: '#dc3545',
                            backgroundColor: 'rgba(220, 53, 69, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    ...chartOptions,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Production Rate (%)'
                            },
                            min: 0,
                            max: 100
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Egg Mortality'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Week'
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Production Rate & Egg Mortality Chart Error:', error);
        }

        // Initialize Flock Capital Chart
        try {
            new Chart(document.getElementById('flockCapitalChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Capital Investment', 'Operational Expenses', 'Net Income'],
                    datasets: [{
                        data: [
                            Math.max(0, {{ $capitalInvestment }}),
                            Math.max(0, {{ $operationalExpenses }}),
                            Math.max(0, {{ $netIncome }})
                        ],
                        backgroundColor: ['#007bff', '#dc3545', '#28a745'],
                        borderColor: ['#007bff', '#dc3545', '#28a745'],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    return `${label}: $${value.toFixed(2)}`;
                                }
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Flock Capital Chart Error:', error);
        }
    });
</script>
@endsection