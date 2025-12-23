@extends('layouts.master')
@section('content')

<style>
    /* Fix chart container overflow */
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
        overflow: hidden;
    }
    
    /* Fix card layout */
    .card-body {
        overflow-x: hidden;
    }
    
    /* Ensure container doesn't overflow */
    .container-fluid {
        max-width: 100%;
        overflow-x: hidden;
    }
    
    /* Fix chart canvas sizing */
    canvas {
        max-width: 100%;
        height: auto !important;
    }
    
    /* Status badges */
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
    
    /* Improved tab styling */
    .nav-tabs .nav-link {
        font-weight: 500;
    }
    
    /* Summary cards */
    .summary-card {
        border-left: 4px solid #0d6efd;
    }
    .summary-card.success {
        border-left-color: #198754;
    }
    .summary-card.warning {
        border-left-color: #ffc107;
    }
    .summary-card.danger {
        border-left-color: #dc3545;
    }
    .summary-card.info {
        border-left-color: #0dcaf0;
    }
    
    /* Metric card styling */
    .metric-card .metric-value {
        font-size: 1.75rem;
        font-weight: 600;
        line-height: 1.2;
    }
    .metric-card .metric-label {
        font-size: 0.875rem;
        color: #6c757d;
    }
    
    /* KPI badges */
    .kpi-badge {
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-weight: 500;
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
                        <option value="">All Flocks ({{ $allFlocks->count() }})</option>
                        @foreach ($allFlocks as $flock)
                            <option value="{{ $flock->id }}" {{ $flockId == $flock->id ? 'selected' : '' }}>
                                Flock {{ $flock->id }} - 
                                {{ $flock->initial_bird_count }} birds - 
                                <span class="{{ $flock->status === 'active' ? 'text-success' : ($flock->status === 'completed' ? 'text-warning' : 'text-danger') }}">
                                    {{ ucfirst($flock->status) }}
                                </span>
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

            <!-- Flock Status Tabs -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Flock Status Overview</h5>
                        </div>
                        <div class="card-body p-0">
                            <ul class="nav nav-tabs" id="flockStatusTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="active-flocks-tab" data-bs-toggle="tab" 
                                            data-bs-target="#active-flocks" type="button" role="tab">
                                        Active Flocks ({{ $activeFlocks->count() }})
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="inactive-flocks-tab" data-bs-toggle="tab" 
                                            data-bs-target="#inactive-flocks" type="button" role="tab">
                                        Inactive Flocks ({{ $inactiveFlocks->count() }})
                                    </button>
                                </li>
                                @if($flockId)
                                <li class="nav-item ms-auto me-3 mt-2">
                                    <a href="{{ route('dashboard', ['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-grid me-1"></i> View All Flocks
                                    </a>
                                </li>
                                @endif
                            </ul>
                            <div class="tab-content p-3" id="flockStatusTabsContent">
                                <!-- Active Flocks Tab -->
                                <div class="tab-pane fade show active" id="active-flocks" role="tabpanel" aria-labelledby="active-flocks-tab">
                                    @if($activeFlocks->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Flock ID</th>
                                                        <th>Initial Birds</th>
                                                        <th>Current Birds</th>
                                                        <th>Mortality</th>
                                                        <th>Mortality Rate</th>
                                                        <th>Production Rate</th>
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
                                                        $productionRate = $flockProductionRates[$flock->id] ?? 0;
                                                    @endphp
                                                    <tr>
                                                        <td><strong>Flock {{ $flock->id }}</strong></td>
                                                        <td>{{ number_format($flockData['totalBirds'] ?? $flock->initial_bird_count, 0) }}</td>
                                                        <td>{{ number_format($flockData['currentBirds'] ?? $flock->initial_bird_count, 0) }}</td>
                                                        <td class="text-danger">{{ number_format($flockData['totalMortality'] ?? 0, 0) }}</td>
                                                        <td class="text-danger">{{ number_format($mortalityRate, 1) }}%</td>
                                                        <td>
                                                            <span class="{{ $productionRate >= 70 ? 'text-success' : ($productionRate >= 50 ? 'text-warning' : 'text-danger') }}">
                                                                {{ number_format($productionRate, 1) }}%
                                                            </span>
                                                        </td>
                                                        <td>{{ $flockAge }}</td>
                                                        <td>
                                                            <span class="status-badge status-active">
                                                                Active
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('dashboard', ['flock_id' => $flock->id, 'start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}" 
                                                               class="btn btn-sm btn-outline-primary">
                                                                View Analytics
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                    @if($activeFlockAnalysis['flockCount'] > 0)
                                                    <tr class="table-light fw-bold">
                                                        <td>Total Active Flocks</td>
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
                                                        <td>{{ number_format($avgProductionRate, 1) }}%</td>
                                                        <td>-</td>
                                                        <td>-</td>
                                                        <td>
                                                            <span class="badge bg-success">Combined</span>
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
                                                <thead>
                                                    <tr>
                                                        <th>Flock ID</th>
                                                        <th>Initial Birds</th>
                                                        <th>Final Birds</th>
                                                        <th>Total Mortality</th>
                                                        <th>Mortality Rate</th>
                                                        <th>Total Eggs</th>
                                                        <th>Eggs Sold</th>
                                                        <th>Age (Weeks)</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($inactiveFlocks as $flock)
                                                    @php
                                                        $flockData = $inactiveFlockAnalysis['flocks'][$flock->id] ?? null;
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
                                                        <td>{{ number_format($inactiveProductionMetrics['total_egg_pieces'] ?? 0, 0) }}</td>
                                                        <td>{{ number_format($inactiveProductionMetrics['total_sold_pieces'] ?? 0, 0) }}</td>
                                                        <td>{{ $flockAge }}</td>
                                                        <td>
                                                            <span class="status-badge {{ $flock->status === 'completed' ? 'status-completed' : 'status-inactive' }}">
                                                                {{ ucfirst($flock->status) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('dashboard', ['flock_id' => $flock->id, 'start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}" 
                                                               class="btn btn-sm btn-outline-secondary">
                                                                View History
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                    @if($inactiveFlockAnalysis['flockCount'] > 0)
                                                    <tr class="table-light fw-bold">
                                                        <td>Total Inactive Flocks</td>
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
                                                        <td>{{ number_format($inactiveProductionMetrics['total_sold_pieces'] ?? 0, 0) }}</td>
                                                        <td>-</td>
                                                        <td>-</td>
                                                        <td>
                                                            <span class="badge bg-secondary">Historical</span>
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

            <!-- Main Dashboard Content -->
            @if(!$flockId || ($selectedFlock && $selectedFlock->status === 'active'))
            <!-- Active Flocks Dashboard Content -->
            <div id="active-flocks-dashboard">
                @if($flockId && $selectedFlock)
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="alert alert-primary">
                            <h5 class="alert-heading">
                                <i class="bi bi-info-circle me-2"></i>
                                Viewing Analytics for Flock {{ $selectedFlock->id }}
                            </h5>
                            <p class="mb-0">
                                <strong>Period:</strong> {{ $startDate->format('M d, Y') }} to {{ $endDate->format('M d, Y') }} | 
                                <strong>Days:</strong> {{ $daysCount }} | 
                                <strong>Entries:</strong> {{ $flockAnalysis['totalEntries'] ?? 0 }}
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Key Metrics Row -->
                <div class="row">
                    <!-- Total Birds Card -->
                    <div class="col-xxl-3 col-md-6">
                        <div class="card summary-card">
                            <div class="card-body">
                                <div class="d-flex">
                                    <div class="flex-grow-1">
                                        <p class="fs-md text-muted mb-2">Total Birds</p>
                                        <h2 class="mb-1 text-primary">{{ number_format($totalBirds, 0) }}</h2>
                                        <small class="text-muted">Initial bird count</small>
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

                    <!-- Current Birds Card -->
                    <div class="col-xxl-3 col-md-6">
                        <div class="card summary-card success">
                            <div class="card-body">
                                <div class="d-flex">
                                    <div class="flex-grow-1">
                                        <p class="fs-md text-muted mb-2">Current Birds</p>
                                        <h2 class="mb-1 text-success">{{ number_format($currentBirds, 0) }}</h2>
                                        <small class="text-muted">Latest bird count</small>
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

                    <!-- Bird Mortality Card -->
                    <div class="col-xxl-3 col-md-6">
                        <div class="card summary-card danger">
                            <div class="card-body">
                                <div class="d-flex">
                                    <div class="flex-grow-1">
                                        <p class="fs-md text-muted mb-2">Bird Mortality</p>
                                        <h2 class="mb-1 text-danger">{{ number_format($totalMortality, 0) }}</h2>
                                        <small class="text-muted">{{ number_format($birdMortalityRate, 1) }}% mortality rate</small>
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

                    <!-- Production Rate Card -->
                    <div class="col-xxl-3 col-md-6">
                        <div class="card summary-card info">
                            <div class="card-body">
                                <div class="d-flex">
                                    <div class="flex-grow-1">
                                        <p class="fs-md text-muted mb-2">Production Rate</p>
                                        <h2 class="mb-1 text-info">{{ number_format($avgProductionRate, 1) }}%</h2>
                                        <small class="text-muted">
                                            @if($hasDataQualityIssues)
                                            (Excludes unrealistic entries)
                                            @else
                                            Average eggs per bird per day
                                            @endif
                                        </small>
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
                </div>

                <!-- Production Summary Row -->
                <div class="row mt-3">
                    <!-- Egg Production Card -->
                    <div class="col-xxl-4 col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title text-muted mb-3">Total Egg Production</h6>
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h1 class="display-6 text-primary mb-0">
                                            {{ number_format($totalEggProductionCrates, 0) }} Cr
                                        </h1>
                                        <p class="text-muted mb-0">
                                            {{ $totalEggProductionPieces }} Pc | 
                                            {{ number_format($totalEggProductionTotalPieces, 0) }} total eggs
                                        </p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-egg display-6 text-primary opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Eggs Sold Card -->
                    <div class="col-xxl-4 col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title text-muted mb-3">Eggs Sold</h6>
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h1 class="display-6 text-success mb-0">
                                            {{ number_format($totalEggsSoldCrates, 0) }} Cr
                                        </h1>
                                        <p class="text-muted mb-0">
                                            {{ $totalEggsSoldPieces }} Pc | 
                                            {{ number_format($totalEggsSoldTotalPieces, 0) }} eggs sold
                                        </p>
                                        <p class="text-success mb-0 fw-semibold">Revenue: ₦{{ number_format($totalRevenue, 2) }}</p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-cash-coin display-6 text-success opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Egg Mortality Card -->
                    <div class="col-xxl-4 col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title text-muted mb-3">Egg Mortality</h6>
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h1 class="display-6 text-danger mb-0">
                                            {{ number_format($totalEggMortality, 0) }}
                                        </h1>
                                        <p class="text-muted mb-0">
                                            Broken/damaged eggs | 
                                            {{ number_format($eggMortalityRate, 1) }}% of production
                                        </p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-x-circle display-6 text-danger opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feed and Drug Summary -->
                <div class="row mt-3">
                    <!-- Feed Consumption Card -->
                    <div class="col-xxl-4 col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title text-muted mb-3">Feed Consumption</h6>
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h2 class="text-warning mb-1">{{ number_format($totalFeedBags, 2) }} bags</h2>
                                        <p class="text-muted mb-0">
                                            {{ number_format($totalFeedKg, 0) }} kg | 
                                            ₦{{ number_format($feedCost, 2) }} cost
                                        </p>
                                        <small class="text-muted">Avg: {{ number_format($avgDailyFeedCost, 2) }}/day</small>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-basket display-6 text-warning opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Drug Usage Card -->
                    <div class="col-xxl-4 col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title text-muted mb-3">Drug Usage</h6>
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h2 class="text-info mb-1">{{ $totalDrugUsage }} days</h2>
                                        <p class="text-muted mb-0">
                                            Treatment days | 
                                            ₦{{ number_format($drugCost, 2) }} cost
                                        </p>
                                        <small class="text-muted">Avg: {{ number_format($avgDailyDrugCost, 2) }}/day</small>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-capsule display-6 text-info opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Labor Cost Card -->
                    <div class="col-xxl-4 col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title text-muted mb-3">Labor Cost</h6>
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h2 class="text-secondary mb-1">₦{{ number_format($laborCost, 2) }}</h2>
                                        <p class="text-muted mb-0">
                                            {{ $daysCount }} days @ ₦10,000/day
                                        </p>
                                        <small class="text-muted">Part of operational expenses</small>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-person-badge display-6 text-secondary opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="row mt-4">
                    <div class="col-xxl-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Feed Consumption - Last 4 Weeks</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="feedConsumptionChart"></canvas>
                                </div>
                                <div class="text-center mt-2">
                                    <small class="text-muted">Total feed consumed: {{ number_format($totalFeedBags, 2) }} bags ({{ number_format($totalFeedKg, 0) }} kg)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Drug Usage - Treatment Days</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="drugUsageChart"></canvas>
                                </div>
                                <div class="text-center mt-2">
                                    <small class="text-muted">Total treatment days: {{ $totalDrugUsage }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-xxl-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Egg Production vs. Sold</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="eggProductionVsSoldChart"></canvas>
                                </div>
                                <div class="text-center mt-2">
                                    <small class="text-muted">Comparison of eggs produced vs eggs sold</small>
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
                                <div class="text-center mt-2">
                                    <small class="text-muted">Production rate (%) vs Broken eggs count</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Financial Analysis Section -->
                <div class="row mt-4">
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
                                                    <span>Egg Sales ({{ number_format($totalEggsSoldTotalPieces, 0) }} eggs):</span>
                                                    <strong class="text-success">₦{{ number_format($totalRevenue, 2) }}</strong>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Avg Daily Revenue:</span>
                                                    <strong class="text-success">₦{{ number_format($avgDailyRevenue, 2) }}</strong>
                                                </div>
                                                <hr>
                                                <div class="d-flex justify-content-between">
                                                    <span>Total Income:</span>
                                                    <strong class="text-success">₦{{ number_format($totalRevenue, 2) }}</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h6 class="card-title">Expenses</h6>
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
                                                <div class="d-flex justify-content-between">
                                                    <span>Total Expenses:</span>
                                                    <strong class="text-danger">₦{{ number_format($operationalExpenses, 2) }}</strong>
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
                                                    Net Income: ₦{{ number_format($netIncome, 2) }}
                                                </h4>
                                                @if($netIncome < 0)
                                                    <p class="text-danger mb-0">
                                                        Operating at a loss of ₦{{ number_format(abs($netIncome), 2) }}
                                                    </p>
                                                @else
                                                    <p class="text-success mb-0">
                                                        Profitable - ₦{{ number_format($netIncome, 2) }} profit
                                                    </p>
                                                    <small>Profit Margin: {{ $totalRevenue > 0 ? number_format(($netIncome/$totalRevenue)*100, 1) : 0 }}%</small>
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
                                    <span class="text-primary">₦{{ number_format($capitalInvestment, 2) }}</span>
                                    <small class="text-muted d-block">{{ number_format($totalBirds, 0) }} birds × ₦2,000 each</small>
                                </div>
                                <div class="mb-3">
                                    <strong>Operational Expenses:</strong><br>
                                    <span class="text-danger">₦{{ number_format($operationalExpenses, 2) }}</span>
                                    <small class="text-muted d-block">Feed: ₦{{ number_format($feedCost, 2) }}, Drugs: ₦{{ number_format($drugCost, 2) }}, Labor: ₦{{ number_format($laborCost, 2) }}</small>
                                </div>
                                <div class="mb-3">
                                    <strong>Net Income:</strong><br>
                                    <span class="{{ $netIncome < 0 ? 'text-danger' : 'text-success' }}">
                                        ₦{{ number_format($netIncome, 2) }}
                                    </span>
                                </div>
                                <div class="mb-3">
                                    <strong>Capital Value:</strong><br>
                                    <span class="text-info">₦{{ number_format($capitalValue, 2) }}</span>
                                    <small class="text-muted d-block">Based on income approach (10% capitalization rate)</small>
                                </div>
                                <div class="chart-container" style="height: 250px;">
                                    <canvas id="flockCapitalChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance KPIs -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Performance KPIs</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 col-6 mb-3">
                                        <div class="text-center">
                                            <div class="text-muted mb-1">Revenue per Bird</div>
                                            <h4>₦{{ number_format($revenuePerBird, 2) }}</h4>
                                            <small>per bird</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 mb-3">
                                        <div class="text-center">
                                            <div class="text-muted mb-1">Feed per Bird</div>
                                            <h4>{{ number_format($feedPerBird, 2) }}</h4>
                                            <small>bags per bird</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 mb-3">
                                        <div class="text-center">
                                            <div class="text-muted mb-1">Feed Efficiency</div>
                                            <h4>{{ number_format($feedEfficiency, 2) }}</h4>
                                            <small>bags/crate of eggs</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 mb-3">
                                        <div class="text-center">
                                            <div class="text-muted mb-1">Cost per Egg</div>
                                            <h4>₦{{ number_format($costPerEgg, 2) }}</h4>
                                            <small>per egg sold</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 mb-3">
                                        <div class="text-center">
                                            <div class="text-muted mb-1">Avg Daily Production</div>
                                            <h4>{{ number_format($avgDailyProduction, 0) }}</h4>
                                            <small>eggs per day</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 mb-3">
                                        <div class="text-center">
                                            <div class="text-muted mb-1">Avg Daily Birds</div>
                                            <h4>{{ number_format($avgDailyBirds, 0) }}</h4>
                                            <small>birds per day</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 mb-3">
                                        <div class="text-center">
                                            <div class="text-muted mb-1">Egg Sales Efficiency</div>
                                            <h4>{{ number_format($eggSalesEfficiency, 1) }}%</h4>
                                            <small>of available eggs</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 mb-3">
                                        <div class="text-center">
                                            <div class="text-muted mb-1">Egg Disposal Rate</div>
                                            <h4>{{ number_format($eggDisposalRate, 1) }}%</h4>
                                            <small>sold + broken / produced</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Report -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Summary Report</h5>
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
                                        ({{ $totalEggProductionCrates }} crates {{ $totalEggProductionPieces }} pieces) at a 
                                        <strong>{{ number_format($avgProductionRate, 1) }}% production rate</strong>.
                                    </p>
                                    <p>
                                        Of these, <strong>{{ number_format($totalEggsSoldTotalPieces, 0) }} eggs</strong> were sold, 
                                        generating <strong>₦{{ number_format($totalRevenue, 2) }}</strong> in revenue. 
                                        <strong>{{ number_format($totalEggMortality, 0) }} eggs</strong> were broken ({{ number_format($eggMortalityRate, 1) }}% of production).
                                    </p>
                                    <p>
                                        Feed consumption totaled <strong>{{ number_format($totalFeedBags, 1) }} bags</strong> 
                                        ({{ number_format($totalFeedKg, 0) }} kg) costing <strong>₦{{ number_format($feedCost, 2) }}</strong>.
                                        Medication was administered on <strong>{{ $totalDrugUsage }} days</strong> costing <strong>₦{{ number_format($drugCost, 2) }}</strong>.
                                        Labor cost for {{ $daysCount }} days was <strong>₦{{ number_format($laborCost, 2) }}</strong>.
                                    </p>
                                    @if($hasDataQualityIssues)
                                    <div class="alert alert-warning mt-2">
                                        <strong>Note:</strong> {{ count($unrealisticEntries) }} entries were excluded from production rate calculation 
                                        due to unrealistic data (egg production > 110% of bird count).
                                    </div>
                                    @endif
                                    <p class="mb-0">
                                        <strong>Final Result:</strong> 
                                        @if($netIncome < 0)
                                            The operation incurred a loss of <strong>₦{{ number_format(abs($netIncome), 2) }}</strong> 
                                            during this period. Consider reviewing feed efficiency and mortality rates.
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
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <h5 class="alert-heading">
                                <i class="bi bi-clock-history me-2"></i>
                                Historical Data View - Flock {{ $selectedFlock->id }} ({{ ucfirst($selectedFlock->status) }})
                            </h5>
                            <p>This flock is currently {{ $selectedFlock->status }}. Displaying historical data for the selected period.</p>
                        </div>
                    </div>
                </div>

                <!-- Inactive Flock Summary -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Initial Birds</h6>
                                <h2 class="text-primary">{{ number_format($totalBirds, 0) }}</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Final Birds</h6>
                                <h2 class="text-secondary">{{ number_format($currentBirds, 0) }}</h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="text-muted">Total Mortality</h6>
                                <h2 class="text-danger">{{ number_format($totalMortality, 0) }}</h2>
                                <small>{{ number_format($birdMortalityRate, 1) }}%</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Historical Production Summary -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">Total Egg Production</h6>
                                <h3 class="text-primary">
                                    {{ number_format($totalEggProductionCrates, 0) }} Cr 
                                    {{ $totalEggProductionPieces }} Pc
                                </h3>
                                <p class="text-muted mb-0">{{ number_format($totalEggProductionTotalPieces, 0) }} total eggs</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">Eggs Sold</h6>
                                <h3 class="text-success">
                                    {{ number_format($totalEggsSoldCrates, 0) }} Cr 
                                    {{ $totalEggsSoldPieces }} Pc
                                </h3>
                                <p class="text-muted mb-0">{{ number_format($totalEggsSoldTotalPieces, 0) }} eggs sold</p>
                                <p class="text-success mb-0 fw-semibold">Revenue: ₦{{ number_format($totalRevenue, 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Historical Financial Summary -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Historical Financial Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h6 class="card-title">Revenue Generated</h6>
                                                <h4 class="text-success">₦{{ number_format($totalRevenue, 2) }}</h4>
                                                <p class="text-muted mb-0">{{ number_format($totalEggsSoldTotalPieces, 0) }} eggs sold</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h6 class="card-title">Total Expenses</h6>
                                                <h4 class="text-danger">₦{{ number_format($operationalExpenses, 2) }}</h4>
                                                <p class="text-muted mb-0">Feed: ₦{{ number_format($feedCost, 2) }}, Drugs: ₦{{ number_format($drugCost, 2) }}, Labor: ₦{{ number_format($laborCost, 2) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card {{ $netIncome < 0 ? 'bg-danger-subtle' : 'bg-success-subtle' }}">
                                            <div class="card-body">
                                                <h6 class="card-title">Net Income</h6>
                                                <h4 class="{{ $netIncome < 0 ? 'text-danger' : 'text-success' }}">
                                                    ₦{{ number_format($netIncome, 2) }}
                                                </h4>
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

            <!-- Debug Information (Optional - remove in production) -->
            @if(env('APP_DEBUG', false))
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Debug: Flock Analysis</h5>
                        </div>
                        <div class="card-body">
                            <pre>{{ print_r($flockAnalysis, true) }}</pre>
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

        // Initialize Feed Consumption Chart
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
                                text: 'Number of Bags'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value + ' bags';
                                }
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Week'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Feed: ${context.raw.toFixed(2)} bags`;
                                }
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
                        pointBackgroundColor: '#0d6efd',
                        pointRadius: 5
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
                                stepSize: 1,
                                callback: function(value) {
                                    return Number.isInteger(value) ? value : '';
                                }
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Week'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Treatment Days: ${context.raw}`;
                                }
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
                            borderColor: '#28a745',
                            borderWidth: 1,
                            borderRadius: 5,
                        },
                        {
                            label: 'Eggs Sold',
                            data: {!! json_encode(array_values($eggSoldChartData)) !!},
                            backgroundColor: 'rgba(255, 193, 7, 0.8)',
                            borderColor: '#ffc107',
                            borderWidth: 1,
                            borderRadius: 5,
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
                            },
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Week'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.dataset.label;
                                    const value = context.raw;
                                    return `${label}: ${value.toLocaleString()} eggs`;
                                }
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
                            pointBackgroundColor: '#17a2b8',
                            pointRadius: 5
                        },
                        {
                            label: 'Egg Mortality (Broken)',
                            data: {!! json_encode(array_values($eggMortalityChartData)) !!},
                            borderColor: '#dc3545',
                            backgroundColor: 'rgba(220, 53, 69, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y1',
                            pointBackgroundColor: '#dc3545',
                            pointRadius: 5
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
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
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
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Week'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.dataset.label;
                                    const value = context.raw;
                                    if (label.includes('Production Rate')) {
                                        return `${label}: ${value.toFixed(1)}%`;
                                    }
                                    return `${label}: ${value} eggs`;
                                }
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
                        borderColor: ['#0d6efd', '#dc3545', '{{ $netIncome >= 0 ? "#28a745" : "#dc3545" }}'],
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
                                    return `${label}: ₦${value.toFixed(2)}`;
                                }
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Flock Capital Chart Error:', error);
        }
        @endif
    });
</script>
@endsection