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
                            <option value="{{ $flock->id }}" {{ $flockId == $flock->id ? 'selected' : '' }}>Flock {{ $flock->id }}</option>
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
                                    <h3 class="mb-0 mt-auto"><span class="counter-value" data-target="{{ $totalBirds }}">{{ $totalBirds }}</span></h3>
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
                                    <p class="fs-md text-muted mb-4">Current Birds</p>
                                    <h3 class="mb-0 mt-auto"><span class="counter-value" data-target="{{ $currentBirds }}">{{ $currentBirds }}</span></h3>
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
                                    <p class="fs-md text-muted mb-4">Total Egg Prod.</p>
                                    <h3 class="mb-0 mt-auto"><span class="counter-value" data-target="{{ $totalEggProduction }}">{{ number_format($totalEggProduction, 2) }}</span>k</h3>
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
                                    <p class="fs-md text-muted mb-4">Total Mortality</p>
                                    <h3 class="mb-0 mt-auto"><span class="counter-value" data-target="{{ $totalMortality }}">{{ $totalMortality }}</span></h3>
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
                                    <p class="fs-md text-muted mb-4">Feed Consumed</p>
                                    <h3 class="mb-0 mt-auto"><span class="counter-value" data-target="{{ $totalFeedConsumed }}">{{ $totalFeedConsumed }}</span> kg</h3>
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
                                    <h3 class="mb-0 mt-auto"><span class="counter-value" data-target="{{ $totalEggsSold }}">{{ $totalEggsSold }}</span></h3>
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
                                    <h3 class="mb-0 mt-auto"><span class="counter-value" data-target="{{ $avgProductionRate }}">{{ number_format($avgProductionRate, 2) }}</span>%</h3>
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
                                    <h3 class="mb-0 mt-auto"><span class="counter-value" data-target="{{ $totalEggMortality }}">{{ $totalEggMortality }}</span></h3>
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
                            <h5 class="card-title mb-0">Feed Consumption (Last 30 Days)</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="feedConsumptionChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Drug Usage</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="drugUsageChart"></canvas>
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
                            <canvas id="eggProductionVsSoldChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Production Rate & Egg Mortality</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="productionRateAndEggMortalityChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xxl-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Flock Capital Analysis</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Capital Investment:</strong> ${{ number_format($capitalInvestment, 2) }}</p>
                            <p><strong>Operational Expenses:</strong> ${{ number_format($operationalExpenses, 2) }}</p>
                            <p><strong>Net Income:</strong> ${{ number_format($netIncome, 2) }}</p>
                            <p><strong>Capital Value (Income Approach):</strong> ${{ number_format($capitalValue, 2) }}</p>
                            @if ($netIncome < 0)
                                <p class="text-danger">Note: Negative net income indicates operational losses.</p>
                            @endif
                            <canvas id="flockCapitalChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


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
            console.log('Flock Capital Data:', [{{ $capitalInvestment }}, {{ $operationalExpenses }}, {{ $netIncome }}]);

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
                    new CountUp(element, parseFloat(element.getAttribute('data-target'))).start();
                } catch (error) {
                    console.error('CountUp Error:', error);
                }
            });

            // Initialize Feed Consumption Chart
            try {
                new Chart(document.getElementById('feedConsumptionChart'), {
                    type: 'line',
                    data: {
                        labels: {!! json_encode($weeks) !!},
                        datasets: [{
                            label: 'Feed Consumption (kg)',
                            data: {!! json_encode(array_values($feedChartData)) !!},
                            borderColor: '#007bff',
                            backgroundColor: 'rgba(0, 123, 255, 0.2)',
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: { display: true, text: 'Feed (kg)' }
                            },
                            x: {
                                title: { display: true, text: 'Week' }
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
                        labels: {!! json_encode($weeks) !!},
                        datasets: [{
                            label: 'Drug Usage (Units)',
                            data: {!! json_encode(array_values($drugChartData)) !!},
                            backgroundColor: '#dc3545'
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: { display: true, text: 'Units' }
                            },
                            x: {
                                title: { display: true, text: 'Week' }
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
                        labels: {!! json_encode($weeks) !!},
                        datasets: [
                            {
                                label: 'Eggs Produced',
                                data: {!! json_encode(array_values($eggProductionChartData)) !!},
                                backgroundColor: '#28a745'
                            },
                            {
                                label: 'Eggs Sold',
                                data: {!! json_encode(array_values($eggSoldChartData)) !!},
                                backgroundColor: '#ffc107'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: { display: true, text: 'Eggs' }
                            },
                            x: {
                                title: { display: true, text: 'Week' }
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
                        labels: {!! json_encode($weeks) !!},
                        datasets: [
                            {
                                label: 'Production Rate (%)',
                                data: {!! json_encode(array_values($productionRateChartData)) !!},
                                borderColor: '#17a2b8',
                                fill: false
                            },
                            {
                                label: 'Egg Mortality',
                                data: {!! json_encode(array_values($eggMortalityChartData)) !!},
                                borderColor: '#dc3545',
                                fill: false
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: { display: true, text: 'Value' }
                            },
                            x: {
                                title: { display: true, text: 'Week' }
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
                    type: 'pie',
                    data: {
                        labels: ['Capital Investment', 'Operational Expenses', 'Net Income'],
                        datasets: [{
                            data: [{{ $capitalInvestment }}, {{ $operationalExpenses }}, {{ $netIncome < 0 ? 0 : $netIncome }}],
                            backgroundColor: ['#007bff', '#dc3545', '#28a745']
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top'
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
