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
    
    .status-badge {
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .status-active {
        background-color: #d1e7dd;
        color: #0f5132;
    }
    .status-inactive {
        background-color: #f8d7da;
        color: #842029;
    }
    .status-completed {
        background-color: #fff3cd;
        color: #664d03;
    }
    
    .metric-card {
        transition: transform 0.2s;
    }
    .metric-card:hover {
        transform: translateY(-5px);
    }
    
    .nav-tabs .nav-link.active {
        border-bottom: 3px solid #0d6efd;
        font-weight: 600;
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
                        <option value="">All Flocks</option>
                        @foreach ($flocks as $flock)
                            <option value="{{ $flock->id }}" {{ $flockId == $flock->id ? 'selected' : '' }}>
                                Flock {{ $flock->id }} - 
                                <span class="{{ $flock->status === 'active' ? 'text-success' : ($flock->status === 'completed' ? 'text-warning' : 'text-danger') }}">
                                    {{ ucfirst($flock->status) }}
                                </span>
                                ({{ $flock->initial_bird_count }} birds)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="dateRangePicker" class="form-label">Date Range</label>
                    <input type="text" class="form-control" id="dateRangePicker" data-provider="flatpickr" data-range-date="true" data-date-format="Y-m-d" value="{{ $startDate->format('Y-m-d') }} to {{ $endDate->format('Y-m-d') }}">
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button class="btn btn-primary" onclick="applyFilters()">
                        <i class="bi bi-filter me-1"></i> Apply Filters
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-download me-1"></i> Export
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                            <li><a class="dropdown-item" href="{{ route('dashboard.export', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d'), 'flock_id' => $flockId, 'format' => 'csv']) }}">Export to CSV</a></li>
                            <li><a class="dropdown-item" href="{{ route('dashboard.export', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d'), 'flock_id' => $flockId, 'format' => 'pdf']) }}">Export to PDF</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Flock Status Tabs -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Flock Status Overview</h5>
                        </div>
                        <div class="card-body">
                            <ul class="nav nav-tabs" id="flockStatusTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="active-flocks-tab" data-bs-toggle="tab" 
                                            data-bs-target="#active-flocks" type="button" role="tab">
                                        <i class="bi bi-check-circle-fill me-1 text-success"></i>
                                        Active Flocks ({{ $activeFlocks->count() }})
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="inactive-flocks-tab" data-bs-toggle="tab" 
                                            data-bs-target="#inactive-flocks" type="button" role="tab">
                                        <i class="bi bi-x-circle-fill me-1 text-secondary"></i>
                                        Inactive Flocks ({{ $inactiveFlocks->count() }})
                                    </button>
                                </li>
                                @if($flockId)
                                <li class="nav-item ms-auto" role="presentation">
                                    <button class="nav-link btn btn-outline-primary btn-sm" 
                                            onclick="window.location.href='{{ route('dashboard', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}'">
                                        <i class="bi bi-grid me-1"></i> View All Flocks
                                    </button>
                                </li>
                                @endif
                            </ul>
                            <div class="tab-content mt-3" id="flockStatusTabsContent">
                                <!-- Active Flocks Tab -->
                                <div class="tab-pane fade show active" id="active-flocks" role="tabpanel" aria-labelledby="active-flocks-tab">
                                    @if($activeFlocks->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Flock ID</th>
                                                        <th>Initial Birds</th>
                                                        <th>Current Birds</th>
                                                        <th>Mortality</th>
                                                        <th>Mortality Rate</th>
                                                        <th>Age (Weeks)</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($activeFlocks as $flock)
                                                    @php
                                                        $flockData = $activeFlockAnalysis['flocks'][$flock->id] ?? null;
                                                        $flockAge = $flockAges[$flock->id] ?? 0;
                                                        $mortalityRate = $flockData && $flockData['totalBirds'] > 0 
                                                            ? ($flockData['totalMortality'] / $flockData['totalBirds']) * 100 
                                                            : 0;
                                                    @endphp
                                                    <tr>
                                                        <td><strong>Flock {{ $flock->id }}</strong></td>
                                                        <td>{{ number_format($flockData['totalBirds'] ?? $flock->initial_bird_count, 0) }}</td>
                                                        <td>{{ number_format($flockData['currentBirds'] ?? $flock->initial_bird_count, 0) }}</td>
                                                        <td class="text-danger">{{ number_format($flockData['totalMortality'] ?? 0, 0) }}</td>
                                                        <td class="text-danger">{{ number_format($mortalityRate, 1) }}%</td>
                                                        <td>{{ $flockAge }}</td>
                                                        <td>
                                                            <span class="status-badge status-active">
                                                                <i class="bi bi-check-circle me-1"></i>Active
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('dashboard', ['flock_id' => $flock->id, 'start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}" 
                                                               class="btn btn-sm btn-outline-primary">
                                                                <i class="bi bi-graph-up me-1"></i>View Analytics
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                    @if($activeFlockAnalysis['flockCount'] > 0)
                                                    <tr class="table-primary">
                                                        <td><strong>Total Active Flocks</strong></td>
                                                        <td>{{ number_format($activeFlockAnalysis['totalBirdsAll'], 0) }}</td>
                                                        <td>{{ number_format($activeFlockAnalysis['currentBirdsAll'], 0) }}</td>
                                                        <td class="text-danger">{{ number_format($activeFlockAnalysis['totalMortalityAll'], 0) }}</td>
                                                        <td class="text-danger">
                                                            @php
                                                                $totalMortalityRate = $activeFlockAnalysis['totalBirdsAll'] > 0 
                                                                    ? ($activeFlockAnalysis['totalMortalityAll'] / $activeFlockAnalysis['totalBirdsAll']) * 100 
                                                                    : 0;
                                                            @endphp
                                                            {{ number_format($totalMortalityRate, 1) }}%
                                                        </td>
                                                        <td>-</td>
                                                        <td>-</td>
                                                        <td>
                                                            <span class="badge bg-success">Combined View</span>
                                                        </td>
                                                    </tr>
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-info mt-3">
                                            <i class="bi bi-info-circle me-2"></i>
                                            No active flocks found.
                                        </div>
                                    @endif
                                </div>

                                <!-- Inactive Flocks Tab -->
                                <div class="tab-pane fade" id="inactive-flocks" role="tabpanel" aria-labelledby="inactive-flocks-tab">
                                    @if($inactiveFlocks->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Flock ID</th>
                                                        <th>Initial Birds</th>
                                                        <th>Final Birds</th>
                                                        <th>Total Mortality</th>
                                                        <th>Mortality Rate</th>
                                                        <th>Total Eggs</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($inactiveFlocks as $flock)
                                                    @php
                                                        $flockData = $inactiveFlockAnalysis['flocks'][$flock->id] ?? null;
                                                        $mortalityRate = $flockData && $flockData['totalBirds'] > 0 
                                                            ? ($flockData['totalMortality'] / $flockData['totalBirds']) * 100 
                                                            : 0;
                                                    @endphp
                                                    <tr>
                                                        <td><strong>Flock {{ $flock->id }}</strong></td>
                                                        <td>{{ number_format($flockData['totalBirds'] ?? $flock->initial_bird_count, 0) }}</td>
                                                        <td>{{ number_format($flockData['currentBirds'] ?? $flock->initial_bird_count, 0) }}</td>
                                                        <td class="text-danger">{{ number_format($flockData['totalMortality'] ?? 0, 0) }}</td>
                                                        <td class="text-danger">{{ number_format($mortalityRate, 1) }}%</td>
                                                        <td>{{ number_format($inactiveProductionMetrics['total_egg_pieces'] ?? 0, 0) }}</td>
                                                        <td>
                                                            <span class="status-badge {{ $flock->status === 'completed' ? 'status-completed' : 'status-inactive' }}">
                                                                <i class="bi bi-clock-history me-1"></i>{{ ucfirst($flock->status) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('dashboard', ['flock_id' => $flock->id, 'start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}" 
                                                               class="btn btn-sm btn-outline-secondary">
                                                                <i class="bi bi-eye me-1"></i>View History
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                    @if($inactiveFlockAnalysis['flockCount'] > 0)
                                                    <tr class="table-secondary">
                                                        <td><strong>Total Inactive Flocks</strong></td>
                                                        <td>{{ number_format($inactiveFlockAnalysis['totalBirdsAll'], 0) }}</td>
                                                        <td>{{ number_format($inactiveFlockAnalysis['currentBirdsAll'], 0) }}</td>
                                                        <td class="text-danger">{{ number_format($inactiveFlockAnalysis['totalMortalityAll'], 0) }}</td>
                                                        <td class="text-danger">
                                                            @php
                                                                $totalInactiveMortalityRate = $inactiveFlockAnalysis['totalBirdsAll'] > 0 
                                                                    ? ($inactiveFlockAnalysis['totalMortalityAll'] / $inactiveFlockAnalysis['totalBirdsAll']) * 100 
                                                                    : 0;
                                                            @endphp
                                                            {{ number_format($totalInactiveMortalityRate, 1) }}%
                                                        </td>
                                                        <td>{{ number_format($inactiveProductionMetrics['total_egg_pieces'] ?? 0, 0) }}</td>
                                                        <td>-</td>
                                                        <td>
                                                            <span class="badge bg-secondary">Historical Data</span>
                                                        </td>
                                                    </tr>
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-info mt-3">
                                            <i class="bi bi-info-circle me-2"></i>
                                            No inactive flocks found.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Content -->
            @if(!$flockId || ($selectedFlock && $selectedFlock->status === 'active'))
            <!-- Active Flocks Dashboard -->
            <div id="active-flocks-dashboard">
                @if($flockId && $selectedFlock)
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card bg-primary-subtle border-primary">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="card-title text-primary mb-2">
                                            <i class="bi bi-info-circle me-2"></i>
                                            Viewing Analytics for Flock {{ $selectedFlock->id }}
                                        </h5>
                                        <div class="d-flex flex-wrap gap-4">
                                            <div>
                                                <small class="text-muted">Initial Birds</small>
                                                <h6 class="mb-0">{{ number_format($totalBirds, 0) }}</h6>
                                            </div>
                                            <div>
                                                <small class="text-muted">Current Birds</small>
                                                <h6 class="mb-0 text-success">{{ number_format($currentBirds, 0) }}</h6>
                                            </div>
                                            <div>
                                                <small class="text-muted">Mortality</small>
                                                <h6 class="mb-0 text-danger">{{ number_format($totalMortality, 0) }}</h6>
                                            </div>
                                            <div>
                                                <small class="text-muted">Mortality Rate</small>
                                                <h6 class="mb-0 text-danger">{{ number_format($birdMortalityRate, 1) }}%</h6>
                                            </div>
                                            <div>
                                                <small class="text-muted">Age</small>
                                                <h6 class="mb-0">{{ $flockAges[$flockId] ?? 0 }} weeks</h6>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <span class="status-badge status-active fs-6">
                                            <i class="bi bi-check-circle me-1"></i>Active
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Key Metrics -->
                <div class="row mb-4">
                    <!-- Total Birds -->
                    <div class="col-xxl-3 col-md-6">
                        <div class="card metric-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <p class="text-muted mb-2">Total Birds</p>
                                        <h4 class="mb-0">{{ number_format($totalBirds, 0) }}</h4>
                                        <small class="text-muted">Initial count</small>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <i class="bi bi-egg-fried fs-4"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Current Birds -->
                    <div class="col-xxl-3 col-md-6">
                        <div class="card metric-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <p class="text-muted mb-2">Current Birds</p>
                                        <h4 class="mb-0">{{ number_format($currentBirds, 0) }}</h4>
                                        <small class="text-muted">Latest count</small>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <i class="bi bi-people fs-4"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bird Mortality -->
                    <div class="col-xxl-3 col-md-6">
                        <div class="card metric-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <p class="text-muted mb-2">Bird Mortality</p>
                                        <h4 class="mb-0 text-danger">{{ number_format($totalMortality, 0) }}</h4>
                                        <small class="text-muted">{{ number_format($birdMortalityRate, 1) }}% of flock</small>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <i class="bi bi-activity fs-4"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Production Rate -->
                    <div class="col-xxl-3 col-md-6">
                        <div class="card metric-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <p class="text-muted mb-2">Production Rate</p>
                                        <h4 class="mb-0">{{ number_format($avgProductionRate, 1) }}%</h4>
                                        <small class="text-muted">
                                            @if($hasDataQualityIssues)
                                            (Excludes unrealistic entries)
                                            @else
                                            Average eggs per bird
                                            @endif
                                        </small>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="avatar-sm rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <i class="bi bi-graph-up fs-4"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Production Metrics -->
                <div class="row mb-4">
                    <!-- Egg Production -->
                    <div class="col-xxl-4 col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title text-primary mb-3">
                                    <i class="bi bi-egg me-2"></i>Egg Production
                                </h6>
                                <div class="text-center">
                                    <h2 class="mb-2">
                                        {{ number_format($totalEggProductionCrates, 0) }} Cr
                                        <small class="text-muted">{{ $totalEggProductionPieces }} Pc</small>
                                    </h2>
                                    <p class="text-muted mb-0">{{ number_format($totalEggProductionTotalPieces, 0) }} total eggs</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Eggs Sold -->
                    <div class="col-xxl-4 col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title text-success mb-3">
                                    <i class="bi bi-cash-coin me-2"></i>Eggs Sold
                                </h6>
                                <div class="text-center">
                                    <h2 class="mb-2">
                                        {{ number_format($totalEggsSoldCrates, 0) }} Cr
                                        <small class="text-muted">{{ $totalEggsSoldPieces }} Pc</small>
                                    </h2>
                                    <p class="text-muted mb-0">{{ number_format($totalEggsSoldTotalPieces, 0) }} eggs sold</p>
                                    <p class="text-success fw-bold mt-2">₦{{ number_format($totalRevenue, 2) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Egg Mortality -->
                    <div class="col-xxl-4 col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title text-danger mb-3">
                                    <i class="bi bi-x-circle me-2"></i>Egg Mortality
                                </h6>
                                <div class="text-center">
                                    <h2 class="mb-2 text-danger">{{ number_format($totalEggMortality, 0) }}</h2>
                                    <p class="text-muted mb-0">Broken/damaged eggs</p>
                                    <p class="text-danger fw-bold mt-2">{{ number_format($eggMortalityRate, 1) }}% of production</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="row mb-4">
                    <div class="col-xxl-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Feed Consumption (Bags)</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="feedConsumptionChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Drug Usage (Treatment Days)</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="drugUsageChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-xxl-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Egg Production vs Sold</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="eggProductionVsSoldChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Production Rate & Egg Mortality</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="productionRateAndEggMortalityChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Financial Analysis -->
                <div class="row mb-4">
                    <div class="col-xxl-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Financial Breakdown</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card border-success">
                                            <div class="card-body">
                                                <h6 class="card-title text-success">
                                                    <i class="bi bi-arrow-up-circle me-2"></i>Income
                                                </h6>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Egg Sales ({{ number_format($totalEggsSoldTotalPieces, 0) }} eggs):</span>
                                                    <strong class="text-success">₦{{ number_format($totalRevenue, 2) }}</strong>
                                                </div>
                                                <hr>
                                                <div class="d-flex justify-content-between fw-bold">
                                                    <span>Total Income:</span>
                                                    <strong class="text-success">₦{{ number_format($totalRevenue, 2) }}</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-danger">
                                            <div class="card-body">
                                                <h6 class="card-title text-danger">
                                                    <i class="bi bi-arrow-down-circle me-2"></i>Expenses
                                                </h6>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Feed Cost ({{ number_format($totalFeedBags, 1) }} bags):</span>
                                                    <strong class="text-danger">₦{{ number_format($feedCost, 2) }}</strong>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Drug Cost ({{ $totalDrugUsage }} days):</span>
                                                    <strong class="text-danger">₦{{ number_format($drugCost, 2) }}</strong>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Labor Cost ({{ $daysCount }} days):</span>
                                                    <strong class="text-danger">₦{{ number_format($laborCost, 2) }}</strong>
                                                </div>
                                                <hr>
                                                <div class="d-flex justify-content-between fw-bold">
                                                    <span>Total Expenses:</span>
                                                    <strong class="text-danger">₦{{ number_format($operationalExpenses, 2) }}</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="card {{ $netIncome < 0 ? 'border-danger bg-danger-subtle' : 'border-success bg-success-subtle' }}">
                                            <div class="card-body text-center">
                                                <h4 class="{{ $netIncome < 0 ? 'text-danger' : 'text-success' }}">
                                                    <i class="bi {{ $netIncome < 0 ? 'bi-arrow-down-circle' : 'bi-arrow-up-circle' }} me-2"></i>
                                                    Net Income: ₦{{ number_format($netIncome, 2) }}
                                                </h4>
                                                @if($netIncome < 0)
                                                    <p class="text-danger mb-0">
                                                        Operating at a loss of ₦{{ number_format(abs($netIncome), 2) }}
                                                    </p>
                                                @else
                                                    <p class="text-success mb-0">
                                                        Profit Margin: {{ $totalRevenue > 0 ? number_format(($netIncome/$totalRevenue)*100, 1) : 0 }}%
                                                    </p>
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
                                    <small class="text-muted">Capital Investment</small>
                                    <h6 class="text-primary">₦{{ number_format($capitalInvestment, 2) }}</h6>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted">Operational Expenses</small>
                                    <h6 class="text-danger">₦{{ number_format($operationalExpenses, 2) }}</h6>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted">Net Income</small>
                                    <h6 class="{{ $netIncome < 0 ? 'text-danger' : 'text-success' }}">
                                        ₦{{ number_format($netIncome, 2) }}
                                    </h6>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted">Capital Value</small>
                                    <h6 class="text-info">₦{{ number_format($capitalValue, 2) }}</h6>
                                </div>
                                <div class="chart-container" style="height: 200px;">
                                    <canvas id="flockCapitalChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Report -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-clipboard-data me-2"></i>Summary Report
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert {{ $netIncome < 0 ? 'alert-danger' : 'alert-success' }}">
                                    <h5 class="alert-heading">Overall Performance Summary</h5>
                                    <p>
                                        During the selected period ({{ $startDate->format('M d, Y') }} to {{ $endDate->format('M d, Y') }}), 
                                        @if($flockId)
                                            Flock {{ $flockId }} 
                                        @else
                                            all active flocks combined
                                        @endif
                                        started with <strong>{{ number_format($totalBirds, 0) }} birds</strong> and currently has 
                                        <strong>{{ number_format($currentBirds, 0) }} birds</strong>.
                                    </p>
                                    <p>
                                        <strong>Mortality:</strong> {{ number_format($totalMortality, 0) }} birds 
                                        ({{ number_format($birdMortalityRate, 1) }}% of flock).
                                    </p>
                                    <p>
                                        The flock produced <strong>{{ number_format($totalEggProductionTotalPieces, 0) }} eggs</strong> 
                                        ({{ $totalEggProductionCrates }} crates {{ $totalEggProductionPieces }} pieces).
                                    </p>
                                    <p>
                                        Of these, <strong>{{ number_format($totalEggsSoldTotalPieces, 0) }} eggs</strong> were sold, 
                                        generating <strong>₦{{ number_format($totalRevenue, 2) }}</strong> in revenue. 
                                        <strong>{{ number_format($totalEggMortality, 0) }} eggs</strong> were broken ({{ number_format($eggMortalityRate, 1) }}% of production).
                                    </p>
                                    @if($hasDataQualityIssues)
                                    <div class="alert alert-warning mt-2">
                                        <strong>Note:</strong> {{ count($unrealisticEntries) }} entries were excluded from production rate calculation 
                                        due to unrealistic data.
                                    </div>
                                    @endif
                                    <p class="mb-0">
                                        <strong>Final Result:</strong> 
                                        @if($netIncome < 0)
                                            The operation incurred a loss of <strong>₦{{ number_format(abs($netIncome), 2) }}</strong> 
                                            during this period.
                                        @else
                                            The operation generated a profit of <strong>₦{{ number_format($netIncome, 2) }}</strong> 
                                            with a profit margin of {{ $totalRevenue > 0 ? number_format(($netIncome/$totalRevenue)*100, 1) : 0 }}%.
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @elseif($selectedFlock && $selectedFlock->status !== 'active')
            <!-- Inactive Flock Historical View -->
            <div id="inactive-flock-dashboard">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card bg-secondary-subtle border-secondary">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h5 class="card-title text-secondary mb-2">
                                            <i class="bi bi-clock-history me-2"></i>
                                            Historical Data - Flock {{ $selectedFlock->id }} ({{ ucfirst($selectedFlock->status) }})
                                        </h5>
                                        <div class="d-flex flex-wrap gap-4">
                                            <div>
                                                <small class="text-muted">Initial Birds</small>
                                                <h6 class="mb-0">{{ number_format($totalBirds, 0) }}</h6>
                                            </div>
                                            <div>
                                                <small class="text-muted">Final Birds</small>
                                                <h6 class="mb-0">{{ number_format($currentBirds, 0) }}</h6>
                                            </div>
                                            <div>
                                                <small class="text-muted">Total Mortality</small>
                                                <h6 class="mb-0 text-danger">{{ number_format($totalMortality, 0) }}</h6>
                                            </div>
                                            <div>
                                                <small class="text-muted">Mortality Rate</small>
                                                <h6 class="mb-0 text-danger">{{ number_format($birdMortalityRate, 1) }}%</h6>
                                            </div>
                                            <div>
                                                <small class="text-muted">Age</small>
                                                <h6 class="mb-0">{{ $flockAges[$flockId] ?? 0 }} weeks</h6>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <span class="status-badge {{ $selectedFlock->status === 'completed' ? 'status-completed' : 'status-inactive' }} fs-6">
                                            <i class="bi bi-clock-history me-1"></i>{{ ucfirst($selectedFlock->status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Historical Metrics -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="card-title text-primary">Total Egg Production</h6>
                                <h2 class="text-primary">
                                    {{ number_format($totalEggProductionCrates, 0) }} Cr
                                    <small class="text-muted">{{ $totalEggProductionPieces }} Pc</small>
                                </h2>
                                <p class="text-muted mb-0">{{ number_format($totalEggProductionTotalPieces, 0) }} eggs</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="card-title text-success">Eggs Sold</h6>
                                <h2 class="text-success">
                                    {{ number_format($totalEggsSoldCrates, 0) }} Cr
                                    <small class="text-muted">{{ $totalEggsSoldPieces }} Pc</small>
                                </h2>
                                <p class="text-muted mb-0">{{ number_format($totalEggsSoldTotalPieces, 0) }} eggs</p>
                                <p class="text-success fw-bold mt-2">₦{{ number_format($totalRevenue, 2) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="card-title text-danger">Egg Mortality</h6>
                                <h2 class="text-danger">{{ number_format($totalEggMortality, 0) }}</h2>
                                <p class="text-muted mb-0">{{ number_format($eggMortalityRate, 1) }}% of production</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Historical Financial Summary -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Historical Financial Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="card border-success">
                                            <div class="card-body text-center">
                                                <h6 class="card-title text-success">Revenue Generated</h6>
                                                <h3 class="text-success">₦{{ number_format($totalRevenue, 2) }}</h3>
                                                <p class="text-muted mb-0">{{ number_format($totalEggsSoldTotalPieces, 0) }} eggs sold</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card border-danger">
                                            <div class="card-body text-center">
                                                <h6 class="card-title text-danger">Total Expenses</h6>
                                                <h3 class="text-danger">₦{{ number_format($operationalExpenses, 2) }}</h3>
                                                <p class="text-muted mb-0">During active period</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card {{ $netIncome < 0 ? 'border-danger bg-danger-subtle' : 'border-success bg-success-subtle' }}">
                                            <div class="card-body text-center">
                                                <h6 class="card-title {{ $netIncome < 0 ? 'text-danger' : 'text-success' }}">Net Income</h6>
                                                <h3 class="{{ $netIncome < 0 ? 'text-danger' : 'text-success' }}">
                                                    ₦{{ number_format($netIncome, 2) }}
                                                </h3>
                                                <p class="{{ $netIncome < 0 ? 'text-danger' : 'text-success' }} mb-0">
                                                    @if($netIncome < 0)
                                                        Loss during period
                                                    @else
                                                        Profit during period
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    </div>
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

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/countup.js/2.8.0/countUp.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Flatpickr
        flatpickr('#dateRangePicker', {
            mode: 'range',
            dateFormat: 'Y-m-d',
            defaultDate: ['{{ $startDate->format('Y-m-d') }}', '{{ $endDate->format('Y-m-d') }}']
        });

        // Apply filters function
        window.applyFilters = function() {
            const dateRange = document.getElementById('dateRangePicker').value;
            const flockId = document.getElementById('flockFilter').value;
            
            let url = '{{ route('dashboard') }}?';
            const params = new URLSearchParams();
            
            if (dateRange) {
                const dates = dateRange.split(' to ');
                if (dates.length === 2) {
                    params.append('start_date', dates[0]);
                    params.append('end_date', dates[1]);
                }
            }
            
            if (flockId) {
                params.append('flock_id', flockId);
            }
            
            window.location.href = url + params.toString();
        };

        // Initialize Counter Animations
        document.querySelectorAll('.counter-value').forEach(function(element) {
            try {
                const targetValue = parseFloat(element.getAttribute('data-target'));
                const countUp = new CountUp(element, targetValue, {
                    duration: 2,
                    separator: ',',
                    decimal: '.',
                    decimalPlaces: element.textContent.includes('₦') ? 2 : 
                                  element.textContent.includes('%') ? 1 : 0
                });
                if (!countUp.error) {
                    countUp.start();
                }
            } catch (error) {
                console.error('CountUp Error:', error);
            }
        });

        // Only initialize charts if we're viewing active flocks
        @if(!$flockId || ($selectedFlock && $selectedFlock->status === 'active'))
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

        // Week labels
        const weekLabels = {!! json_encode($weeks->map(function($week) {
            return 'Week ' . substr($week, -2);
        })) !!};

        // Feed Consumption Chart
        try {
            const feedCtx = document.getElementById('feedConsumptionChart').getContext('2d');
            new Chart(feedCtx, {
                type: 'bar',
                data: {
                    labels: weekLabels,
                    datasets: [{
                        label: 'Feed Consumption (Bags)',
                        data: {!! json_encode(array_values($feedChartData)) !!},
                        backgroundColor: 'rgba(255, 193, 7, 0.8)',
                        borderColor: '#ffc107',
                        borderWidth: 1,
                        borderRadius: 5,
                    }]
                },
                options: {
                    ...chartOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Bags'
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Feed Chart Error:', error);
        }

        // Drug Usage Chart
        try {
            const drugCtx = document.getElementById('drugUsageChart').getContext('2d');
            new Chart(drugCtx, {
                type: 'line',
                data: {
                    labels: weekLabels,
                    datasets: [{
                        label: 'Treatment Days',
                        data: {!! json_encode(array_values($drugChartData)) !!},
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        borderColor: '#0d6efd',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                    }]
                },
                options: chartOptions
            });
        } catch (error) {
            console.error('Drug Chart Error:', error);
        }

        // Egg Production vs Sold Chart
        try {
            const eggVsSoldCtx = document.getElementById('eggProductionVsSoldChart').getContext('2d');
            new Chart(eggVsSoldCtx, {
                type: 'bar',
                data: {
                    labels: weekLabels,
                    datasets: [
                        {
                            label: 'Eggs Produced',
                            data: {!! json_encode(array_values($eggProductionChartData)) !!},
                            backgroundColor: 'rgba(40, 167, 69, 0.8)',
                        },
                        {
                            label: 'Eggs Sold',
                            data: {!! json_encode(array_values($eggSoldChartData)) !!},
                            backgroundColor: 'rgba(255, 193, 7, 0.8)',
                        }
                    ]
                },
                options: chartOptions
            });
        } catch (error) {
            console.error('Egg vs Sold Chart Error:', error);
        }

        // Production Rate & Egg Mortality Chart
        try {
            const productionRateCtx = document.getElementById('productionRateAndEggMortalityChart').getContext('2d');
            new Chart(productionRateCtx, {
                type: 'line',
                data: {
                    labels: weekLabels,
                    datasets: [
                        {
                            label: 'Production Rate (%)',
                            data: {!! json_encode(array_values($productionRateChartData)) !!},
                            borderColor: '#17a2b8',
                            backgroundColor: 'rgba(23, 162, 184, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y',
                        },
                        {
                            label: 'Egg Mortality',
                            data: {!! json_encode(array_values($eggMortalityChartData)) !!},
                            borderColor: '#dc3545',
                            backgroundColor: 'rgba(220, 53, 69, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y1',
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
                            max: 100,
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Egg Mortality Count'
                            },
                            grid: {
                                drawOnChartArea: false
                            },
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Production Rate Chart Error:', error);
        }

        // Flock Capital Chart
        try {
            const capitalCtx = document.getElementById('flockCapitalChart').getContext('2d');
            new Chart(capitalCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Capital Investment', 'Operational Expenses', 'Net Income'],
                    datasets: [{
                        data: [
                            Math.max(0, {{ $capitalInvestment }}),
                            Math.max(0, {{ $operationalExpenses }}),
                            Math.max(0, {{ $netIncome }})
                        ],
                        backgroundColor: ['#0d6efd', '#dc3545', '{{ $netIncome >= 0 ? "#28a745" : "#dc3545" }}'],
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Capital Chart Error:', error);
        }
        @endif
    });
</script>
@endsection