@extends('layouts.master')
@section('content')

<style>
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
        overflow: hidden;
    }
    
    .card-body {
        overflow-x: hidden;
    }
    
    .container-fluid {
        max-width: 100%;
        overflow-x: hidden;
    }
    
    canvas {
        max-width: 100%;
        height: auto !important;
    }
    
    .currency {
        font-family: Arial, sans-serif;
    }
    
    .accordion-button:not(.collapsed) {
        background-color: #e7f1ff;
        color: #0c63e4;
    }
    
    .inactive-flock {
        opacity: 0.8;
        background-color: #f8f9fa;
    }
    
    .flock-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
</style>

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

            <!-- Data Quality Warning -->
            @if($hasDataQualityIssues)
            <div class="row mb-3">
                <div class="col-12">
                    <div class="alert alert-warning">
                        <h5 class="alert-heading">⚠️ Data Quality Issues Detected</h5>
                        <p>Found {{ count($unrealisticEntries) }} entries with unrealistic egg production data.</p>
                        <p><strong>Note:</strong> Production rate calculation excludes unrealistic entries (>110% of bird count).</p>
                        <button class="btn btn-sm btn-outline-warning mt-2" type="button" data-bs-toggle="collapse" data-bs-target="#dataIssuesDetails">
                            Show Details
                        </button>
                        <div class="collapse mt-2" id="dataIssuesDetails">
                            <div class="card card-body">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Entry ID</th>
                                            <th>Date</th>
                                            <th>Birds</th>
                                            <th>Eggs Reported</th>
                                            <th>Rate</th>
                                            <th>Flock</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($unrealisticEntries as $entry)
                                        <tr>
                                            <td>{{ $entry['id'] }}</td>
                                            <td>{{ $entry['date'] }}</td>
                                            <td>{{ number_format($entry['birds'], 0) }}</td>
                                            <td>{{ number_format($entry['eggs'], 0) }}</td>
                                            <td class="text-danger">{{ number_format($entry['rate'], 1) }}%</td>
                                            <td>Flock {{ $entry['flock_id'] ?? 'N/A' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Filters -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="flockFilter" class="form-label">Filter by Flock</label>
                    <select id="flockFilter" name="flock_id" class="form-select">
                        <option value="">All Active Flocks</option>
                        <optgroup label="Active Flocks">
                            @foreach ($activeFlocks as $flock)
                                <option value="{{ $flock->id }}" {{ $flockId == $flock->id ? 'selected' : '' }}>
                                    Flock {{ $flock->id }} - {{ $flock->name }} ({{ $flock->initial_bird_count }} birds)
                                </option>
                            @endforeach
                        </optgroup>
                        @if($inactiveFlocks->count() > 0)
                        <optgroup label="Inactive Flocks">
                            @foreach ($inactiveFlocks as $flock)
                                <option value="{{ $flock->id }}" {{ $flockId == $flock->id ? 'selected' : '' }}>
                                    Flock {{ $flock->id }} - {{ $flock->name }} (Inactive)
                                </option>
                            @endforeach
                        </optgroup>
                        @endif
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

            <!-- Flock Status Summary -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">Active Flocks</h6>
                            <h2 class="text-success">{{ $activeFlocks->count() }}</h2>
                            <div class="d-flex justify-content-between">
                                <span>Total Birds:</span>
                                <strong>{{ number_format($activeFlockAnalysis['totalBirdsAll'] ?? 0, 0) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Current Birds:</span>
                                <strong>{{ number_format($activeFlockAnalysis['currentBirdsAll'] ?? 0, 0) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">Inactive Flocks</h6>
                            <h2 class="text-secondary">{{ $inactiveFlocks->count() }}</h2>
                            <div class="d-flex justify-content-between">
                                <span>Total Birds:</span>
                                <strong>{{ number_format($inactiveFlockAnalysis['totalBirdsAll'] ?? 0, 0) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Current Birds:</span>
                                <strong>{{ number_format($inactiveFlockAnalysis['currentBirdsAll'] ?? 0, 0) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">Overall Summary</h6>
                            <h2 class="text-primary">{{ $flocks->count() }}</h2>
                            <div class="d-flex justify-content-between">
                                <span>Total Birds:</span>
                                <strong>{{ number_format($flockAnalysis['totalBirdsAll'] ?? 0, 0) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Current Birds:</span>
                                <strong>{{ number_format($flockAnalysis['currentBirdsAll'] ?? 0, 0) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Analytics Dashboard -->
            <div class="accordion mb-4" id="flocksAccordion">
                <!-- Active Flocks Section -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="activeFlocksHeading">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#activeFlocksCollapse" aria-expanded="true" aria-controls="activeFlocksCollapse">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            Active Flocks Analytics ({{ $activeFlocks->count() }} flocks)
                            <span class="badge bg-success ms-2">{{ number_format($activeFlockAnalysis['totalBirdsAll'] ?? 0, 0) }} birds</span>
                        </button>
                    </h2>
                    <div id="activeFlocksCollapse" class="accordion-collapse collapse show" aria-labelledby="activeFlocksHeading" data-bs-parent="#flocksAccordion">
                        <div class="accordion-body">
                            @include('dashboards.partials.flock-analytics', [
                                'flockAnalysis' => $activeFlockAnalysis,
                                'flocks' => $activeFlocks,
                                'prefix' => 'active'
                            ])
                        </div>
                    </div>
                </div>

                <!-- Inactive Flocks Section -->
                @if($inactiveFlocks->count() > 0)
                <div class="accordion-item">
                    <h2 class="accordion-header" id="inactiveFlocksHeading">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#inactiveFlocksCollapse" aria-expanded="false" aria-controls="inactiveFlocksCollapse">
                            <i class="bi bi-x-circle-fill text-secondary me-2"></i>
                            Inactive Flocks History ({{ $inactiveFlocks->count() }} flocks)
                            <span class="badge bg-secondary ms-2">{{ number_format($inactiveFlockAnalysis['totalBirdsAll'] ?? 0, 0) }} birds</span>
                        </button>
                    </h2>
                    <div id="inactiveFlocksCollapse" class="accordion-collapse collapse" aria-labelledby="inactiveFlocksHeading" data-bs-parent="#flocksAccordion">
                        <div class="accordion-body">
                            @include('dashboards.partials.flock-analytics', [
                                'flockAnalysis' => $inactiveFlockAnalysis,
                                'flocks' => $inactiveFlocks,
                                'prefix' => 'inactive'
                            ])
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Charts Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Performance Charts</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-xxl-6">
                                    <div class="chart-container">
                                        <canvas id="feedConsumptionChart"></canvas>
                                    </div>
                                    <div class="text-center mt-2">
                                        <small class="text-muted">Feed consumption in bags</small>
                                    </div>
                                </div>
                                <div class="col-xxl-6">
                                    <div class="chart-container">
                                        <canvas id="eggProductionVsSoldChart"></canvas>
                                    </div>
                                    <div class="text-center mt-2">
                                        <small class="text-muted">Eggs produced vs sold</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Flock Information -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Flock Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Flock ID</th>
                                            <th>Status</th>
                                            <th>Initial Birds</th>
                                            <th>Current Birds</th>
                                            <th>Mortality</th>
                                            <th>Mortality Rate</th>
                                            <th>Age (Weeks)</th>
                                            <th>First Entry</th>
                                            <th>Last Entry</th>
                                            <th>Entries</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($flocks as $flock)
                                        @php
                                            $flockData = $flockAnalysis['flocks'][$flock->id] ?? null;
                                            $mortalityRate = $flockData ? ($flockData['totalMortality'] / $flockData['totalBirds'] * 100) : 0;
                                        @endphp
                                        <tr class="{{ $flock->status != 'active' ? 'inactive-flock' : '' }}">
                                            <td>
                                                <strong>Flock {{ $flock->id }}</strong>
                                                @if($flock->name)
                                                <br><small class="text-muted">{{ $flock->name }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge {{ $flock->status == 'active' ? 'bg-success' : 'bg-secondary' }} flock-badge">
                                                    {{ ucfirst($flock->status) }}
                                                </span>
                                            </td>
                                            <td>{{ $flockData ? number_format($flockData['totalBirds'], 0) : 'N/A' }}</td>
                                            <td>{{ $flockData ? number_format($flockData['currentBirds'], 0) : 'N/A' }}</td>
                                            <td class="{{ $flockData && $flockData['totalMortality'] > 0 ? 'text-danger' : '' }}">
                                                {{ $flockData ? number_format($flockData['totalMortality'], 0) : 'N/A' }}
                                            </td>
                                            <td>{{ $flockData ? number_format($mortalityRate, 1) . '%' : 'N/A' }}</td>
                                            <td>{{ $flockAges[$flock->id] ?? 0 }}</td>
                                            <td>{{ $flockData['firstDate'] ?? 'N/A' }}</td>
                                            <td>{{ $flockData['lastDate'] ?? 'N/A' }}</td>
                                            <td>{{ $flockData['entryCount'] ?? 0 }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Debug Section (Optional) -->
            @if(app()->environment('local') && isset($flockAnalysis))
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Debug Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Active Flocks Analysis:</h6>
                                    <pre style="font-size: 11px;">{{ print_r($activeFlockAnalysis, true) }}</pre>
                                </div>
                                <div class="col-md-6">
                                    <h6>Inactive Flocks Analysis:</h6>
                                    <pre style="font-size: 11px;">{{ print_r($inactiveFlockAnalysis, true) }}</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Create partial view for flock analytics -->
@if(!view()->exists('dashboards.partials.flock-analytics'))
<div style="display: none;">
    @section('flock-analytics-partial')
    <div class="row">
        <!-- Flock Info Summary -->
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Flock Information</h6>
                    <div class="row">
                        <div class="col-6">
                            <p class="mb-1"><strong>Total Birds:</strong></p>
                            <h4 class="text-primary">{{ number_format($flockAnalysis['totalBirdsAll'] ?? 0, 0) }}</h4>
                        </div>
                        <div class="col-6">
                            <p class="mb-1"><strong>Current Birds:</strong></p>
                            <h4 class="text-success">{{ number_format($flockAnalysis['currentBirdsAll'] ?? 0, 0) }}</h4>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12">
                            <p class="mb-1"><strong>Total Mortality:</strong></p>
                            <h4 class="text-danger">{{ number_format($flockAnalysis['totalMortalityAll'] ?? 0, 0) }}</h4>
                            <small class="text-muted">
                                Mortality Rate: {{ $flockAnalysis['totalBirdsAll'] > 0 ? number_format(($flockAnalysis['totalMortalityAll'] / $flockAnalysis['totalBirdsAll']) * 100, 1) : 0 }}%
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Flock Details -->
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Flock Details</h6>
                    @foreach($flocks as $flock)
                        @if(isset($flockAnalysis['flocks'][$flock->id]))
                        @php
                            $data = $flockAnalysis['flocks'][$flock->id];
                            $mortalityRate = $data['totalBirds'] > 0 ? ($data['totalMortality'] / $data['totalBirds'] * 100) : 0;
                        @endphp
                        <div class="d-flex justify-content-between mb-2">
                            <span>Flock {{ $flock->id }}:</span>
                            <div>
                                <span class="badge bg-info">{{ $data['currentBirds'] }} birds</span>
                                <span class="badge bg-danger ms-1">{{ $data['totalMortality'] }} dead</span>
                                <small class="text-muted ms-1">({{ number_format($mortalityRate, 1) }}%)</small>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    
    <!-- Individual Flock Performance -->
    <div class="row">
        @foreach($flocks as $flock)
            @if(isset($flockAnalysis['flocks'][$flock->id]))
            @php
                $data = $flockAnalysis['flocks'][$flock->id];
                $mortalityRate = $data['totalBirds'] > 0 ? ($data['totalMortality'] / $data['totalBirds'] * 100) : 0;
            @endphp
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="card-title mb-0">Flock {{ $flock->id }}</h6>
                            <span class="badge {{ $flock->status == 'active' ? 'bg-success' : 'bg-secondary' }}">
                                {{ ucfirst($flock->status) }}
                            </span>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Initial</small>
                                <p class="mb-1">{{ number_format($data['totalBirds'], 0) }}</p>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Current</small>
                                <p class="mb-1">{{ number_format($data['currentBirds'], 0) }}</p>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <small class="text-muted">Mortality</small>
                                <p class="mb-1 text-danger">{{ number_format($data['totalMortality'], 0) }}</p>
                                <small class="text-muted">{{ number_format($mortalityRate, 1) }}% rate</small>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">Age: {{ $flockAges[$flock->id] ?? 0 }} weeks</small>
                            <br>
                            <small class="text-muted">First: {{ $data['firstDate'] }}</small>
                            <br>
                            <small class="text-muted">Last: {{ $data['lastDate'] }}</small>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        @endforeach
    </div>
    @endsection
</div>
@endif

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
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

        // Chart configuration
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index',
            }
        };

        // Initialize Feed Consumption Chart
        try {
            const feedCtx = document.getElementById('feedConsumptionChart').getContext('2d');
            new Chart(feedCtx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($weeks->map(function($week) {
                        return 'Week ' . substr($week, -2);
                    })) !!},
                    datasets: [{
                        label: 'Feed Consumption (Bags)',
                        data: {!! json_encode(array_values($feedChartData)) !!},
                        backgroundColor: 'rgba(255, 193, 7, 0.8)',
                        borderColor: '#ffc107',
                        borderWidth: 1,
                        borderRadius: 5,
                    }]
                },
                options: chartOptions
            });
        } catch (error) {
            console.error('Feed Consumption Chart Error:', error);
        }

        // Initialize Egg Production vs. Sold Chart
        try {
            const eggVsSoldCtx = document.getElementById('eggProductionVsSoldChart').getContext('2d');
            new Chart(eggVsSoldCtx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($weeks->map(function($week) {
                        return 'Week ' . substr($week, -2);
                    })) !!},
                    datasets: [
                        {
                            label: 'Eggs Produced',
                            data: {!! json_encode(array_values($eggProductionChartData)) !!},
                            backgroundColor: 'rgba(40, 167, 69, 0.8)',
                            borderColor: '#28a745',
                            borderWidth: 1,
                            borderRadius: 5,
                        },
                        {
                            label: 'Eggs Sold',
                            data: {!! json_encode(array_values($eggSoldChartData)) !!},
                            backgroundColor: 'rgba(13, 110, 253, 0.8)',
                            borderColor: '#0d6efd',
                            borderWidth: 1,
                            borderRadius: 5,
                        }
                    ]
                },
                options: chartOptions
            });
        } catch (error) {
            console.error('Egg Production vs. Sold Chart Error:', error);
        }
    });
</script>
@endsection