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
    
    /* Data type badges */
    .data-type-badge {
        font-size: 0.65rem;
        padding: 0.2rem 0.4rem;
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
    
    /* Dual data display */
    .lifetime-data {
        border-right: 1px solid #dee2e6;
    }
    .date-range-data {
        padding-left: 1rem;
    }
    .data-label {
        font-size: 0.75rem;
        color: #6c757d;
        margin-bottom: 0.25rem;
    }
    .lifetime-value {
        font-size: 1.5rem;
        font-weight: 600;
        color: #0d6efd;
    }
    .date-range-value {
        font-size: 1.25rem;
        font-weight: 600;
        color: #6c757d;
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

            <!-- Date Range Info -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="alert alert-info">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Date Range:</strong> {{ $startDate->format('M d, Y') }} to {{ $endDate->format('M d') }} 
                                ({{ $daysCount }} days)
                                @if($flockId && $selectedFlock)
                                    | <strong>Viewing:</strong> Flock {{ $selectedFlock->id }} ({{ ucfirst($selectedFlock->status) }})
                                @else
                                    | <strong>Viewing:</strong> All Flocks
                                @endif
                            </div>
                            <div class="d-flex gap-2">
                                <span class="badge bg-primary">Lifetime Data</span>
                                <span class="badge bg-secondary">Date Range Data</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

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
                                                        $flockData = $flockAnalysis['flocks'][$flock->id] ?? null;
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
                                                        $flockData = $flockAnalysis['flocks'][$flock->id] ?? null;
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
                                                        <td>{{ number_format($flockData['totalEggsProduced'] ?? 0, 0) }}</td>
                                                        <td>{{ number_format($flockData['totalEggsSold'] ?? 0, 0) }}</td>
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
                                        Average eggs per bird per day (Date Range)
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
                            <h6 class="card-title text-muted mb-3">
                                Total Egg Production
                                <span class="badge bg-primary data-type-badge">Lifetime</span>
                                <span class="badge bg-secondary data-type-badge">Date Range</span>
                            </h6>
                            <div class="row">
                                <div class="col-6 lifetime-data">
                                    <div class="data-label">Lifetime Total</div>
                                    <div class="lifetime-value">
                                        {{ number_format($lifetimeEggsProducedCrates, 0) }} Cr
                                    </div>
                                    <p class="text-muted mb-1">
                                        {{ $lifetimeEggsProducedPieces }} Pc
                                    </p>
                                    <small class="text-muted d-block">
                                        {{ number_format($lifetimeEggsProduced, 0) }} total eggs
                                    </small>
                                </div>
                                <div class="col-6 date-range-data">
                                    <div class="data-label">Date Range ({{ $daysCount }} days)</div>
                                    <div class="date-range-value">
                                        {{ number_format($dateRangeEggsProducedCrates, 0) }} Cr
                                    </div>
                                    <p class="text-muted mb-1">
                                        {{ $dateRangeEggsProducedPieces }} Pc
                                    </p>
                                    <small class="text-muted">
                                        {{ number_format($dateRangeProduction['total_egg_pieces'], 0) }} eggs
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Eggs Sold Card -->
                <div class="col-xxl-4 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title text-muted mb-3">
                                Eggs Sold
                                <span class="badge bg-primary data-type-badge">Lifetime</span>
                                <span class="badge bg-secondary data-type-badge">Date Range</span>
                            </h6>
                            <div class="row">
                                <div class="col-6 lifetime-data">
                                    <div class="data-label">Lifetime Total</div>
                                    <div class="lifetime-value text-success">
                                        {{ number_format($lifetimeEggsSoldCrates, 0) }} Cr
                                    </div>
                                    <p class="text-muted mb-1">
                                        {{ $lifetimeEggsSoldPieces }} Pc
                                    </p>
                                    <small class="text-muted d-block">
                                        {{ number_format($lifetimeEggsSold, 0) }} eggs sold
                                    </small>
                                    <p class="text-success mb-0 fw-semibold">₦{{ number_format($lifetimeRevenue, 2) }}</p>
                                </div>
                                <div class="col-6 date-range-data">
                                    <div class="data-label">Date Range ({{ $daysCount }} days)</div>
                                    <div class="date-range-value text-secondary">
                                        {{ number_format($dateRangeEggsSoldCrates, 0) }} Cr
                                    </div>
                                    <p class="text-muted mb-1">
                                        {{ $dateRangeEggsSoldPieces }} Pc
                                    </p>
                                    <small class="text-muted">
                                        {{ number_format($dateRangeProduction['total_sold_pieces'], 0) }} eggs
                                    </small>
                                    <p class="text-secondary mb-0 fw-semibold">₦{{ number_format($dateRangeRevenue, 2) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Egg Mortality Card -->
                <div class="col-xxl-4 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title text-muted mb-3">
                                Egg Mortality
                                <span class="badge bg-primary data-type-badge">Lifetime</span>
                                <span class="badge bg-secondary data-type-badge">Date Range</span>
                            </h6>
                            <div class="row">
                                <div class="col-6 lifetime-data">
                                    <div class="data-label">Lifetime Total</div>
                                    <div class="lifetime-value text-danger">
                                        {{ number_format($lifetimeBrokenEggs, 0) }}
                                    </div>
                                    <p class="text-muted mb-0">
                                        {{ number_format($lifetimeEggMortalityRate, 1) }}% of production
                                    </p>
                                </div>
                                <div class="col-6 date-range-data">
                                    <div class="data-label">Date Range ({{ $daysCount }} days)</div>
                                    <div class="date-range-value text-secondary">
                                        {{ number_format($dateRangeEggMortality, 0) }}
                                    </div>
                                    <p class="text-muted mb-0">
                                        {{ number_format($dateRangeEggMortalityRate, 1) }}% of production
                                    </p>
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
                            <h6 class="card-title text-muted mb-3">
                                Feed Consumption
                                <span class="badge bg-primary data-type-badge">Lifetime</span>
                                <span class="badge bg-secondary data-type-badge">Date Range</span>
                            </h6>
                            <div class="row">
                                <div class="col-6 lifetime-data">
                                    <div class="data-label">Lifetime Total</div>
                                    <div class="lifetime-value text-warning">
                                        {{ number_format($lifetimeFeedConsumed, 2) }} bags
                                    </div>
                                    <p class="text-muted mb-1">
                                        {{ number_format($lifetimeFeedConsumed * 50, 0) }} kg
                                    </p>
                                    <small class="text-muted d-block">
                                        Cost: ₦{{ number_format($lifetimeFeedCost, 2) }}
                                        <br>
                                        Avg: ₦{{ number_format($avgLifetimeDailyFeedCost, 2) }}/day
                                    </small>
                                </div>
                                <div class="col-6 date-range-data">
                                    <div class="data-label">Date Range ({{ $daysCount }} days)</div>
                                    <div class="date-range-value text-secondary">
                                        {{ number_format($dateRangeFeed['total_feed_bags'], 2) }} bags
                                    </div>
                                    <p class="text-muted mb-1">
                                        {{ number_format($dateRangeFeed['total_feed_kg'], 0) }} kg
                                    </p>
                                    <small class="text-muted">
                                        Cost: ₦{{ number_format($dateRangeFeedCost, 2) }}
                                        <br>
                                        Avg: ₦{{ number_format($avgDailyFeedCost, 2) }}/day
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Drug Usage Card -->
                <div class="col-xxl-4 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title text-muted mb-3">
                                Drug Usage
                                <span class="badge bg-primary data-type-badge">Lifetime</span>
                                <span class="badge bg-secondary data-type-badge">Date Range</span>
                            </h6>
                            <div class="row">
                                <div class="col-6 lifetime-data">
                                    <div class="data-label">Lifetime Total</div>
                                    <div class="lifetime-value text-info">
                                        {{ $lifetimeDrugUsage }} days
                                    </div>
                                    <p class="text-muted mb-1">
                                        Treatment days
                                    </p>
                                    <small class="text-muted d-block">
                                        Cost: ₦{{ number_format($lifetimeDrugCost, 2) }}
                                        <br>
                                        Avg: ₦{{ number_format($avgLifetimeDailyDrugCost, 2) }}/day
                                    </small>
                                </div>
                                <div class="col-6 date-range-data">
                                    <div class="data-label">Date Range ({{ $daysCount }} days)</div>
                                    <div class="date-range-value text-secondary">
                                        {{ $dateRangeDrugUsage }} days
                                    </div>
                                    <p class="text-muted mb-1">
                                        Treatment days
                                    </p>
                                    <small class="text-muted">
                                        Cost: ₦{{ number_format($dateRangeDrugCost, 2) }}
                                        <br>
                                        Avg: ₦{{ number_format($avgDailyDrugCost, 2) }}/day
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Labor Cost Card -->
                <!-- Labor Cost Card -->
                <div class="col-xxl-4 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title text-muted mb-3">
                                Labor Cost
                                <span class="badge bg-primary data-type-badge">Lifetime</span>
                                <span class="badge bg-secondary data-type-badge">Date Range</span>
                            </h6>
                            <div class="row">
                                <div class="col-6 lifetime-data">
                                    <div class="data-label">Lifetime Total</div>
                                    <div class="lifetime-value text-secondary">
                                        ₦{{ number_format($lifetimeLaborCost, 2) }}
                                    </div>
                                    <p class="text-muted mb-0">
                                        {{ $totalLifetimeDays }} days @ ₦10,000/day
                                    </p>
                                </div>
                                <div class="col-6 date-range-data">
                                    <div class="data-label">Date Range ({{ $daysCount }} days)</div>
                                    <div class="date-range-value text-secondary">
                                        ₦{{ number_format($dateRangeLaborCost, 2) }}
                                    </div>
                                    <p class="text-muted mb-0">
                                        {{ $daysCount }} days @ ₦10,000/day
                                    </p>
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
                                <small class="text-muted">Showing date range data only</small>
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
                                <small class="text-muted">Showing date range data only</small>
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
                                <small class="text-muted">Comparison of eggs produced vs eggs sold (Date Range)</small>
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
                                <small class="text-muted">Production rate (%) vs Broken eggs count (Date Range)</small>
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
                            <h5 class="card-title mb-0">
                                Financial Breakdown
                                <span class="badge bg-primary data-type-badge">Lifetime</span>
                                <span class="badge bg-secondary data-type-badge">Date Range</span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card bg-light mb-3">
                                        <div class="card-header">
                                            <h6 class="card-title mb-0">Income</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Lifetime Revenue:</span>
                                                <strong class="text-success">₦{{ number_format($lifetimeRevenue, 2) }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Date Range Revenue:</span>
                                                <strong class="text-success">₦{{ number_format($dateRangeRevenue, 2) }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Avg Daily (Lifetime):</span>
                                                <strong class="text-success">₦{{ number_format($avgLifetimeDailyRevenue, 2) }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>Avg Daily (Date Range):</span>
                                                <strong class="text-success">₦{{ number_format($avgDailyRevenue, 2) }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card bg-light mb-3">
                                        <div class="card-header">
                                            <h6 class="card-title mb-0">Expenses</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Lifetime Feed Cost:</span>
                                                <strong class="text-danger">₦{{ number_format($lifetimeFeedCost, 2) }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Date Range Feed Cost:</span>
                                                <strong class="text-danger">₦{{ number_format($dateRangeFeedCost, 2) }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Lifetime Drug Cost:</span>
                                                <strong class="text-danger">₦{{ number_format($lifetimeDrugCost, 2) }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>Date Range Drug Cost:</span>
                                                <strong class="text-danger">₦{{ number_format($dateRangeDrugCost, 2) }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="card {{ $lifetimeNetIncome < 0 ? 'bg-danger-subtle' : 'bg-success-subtle' }}">
                                        <div class="card-body text-center">
                                            <h6>Lifetime Net Income</h6>
                                            <h4 class="{{ $lifetimeNetIncome < 0 ? 'text-danger' : 'text-success' }}">
                                                ₦{{ number_format($lifetimeNetIncome, 2) }}
                                            </h4>
                                            @if($lifetimeNetIncome < 0)
                                                <p class="text-danger mb-0">
                                                    Operating at a loss
                                                </p>
                                            @else
                                                <p class="text-success mb-0">
                                                    Profitable operation
                                                </p>
                                                <small>Profit Margin: {{ $lifetimeRevenue > 0 ? number_format(($lifetimeNetIncome/$lifetimeRevenue)*100, 1) : 0 }}%</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6>Date Range Performance</h6>
                                            <h4 class="{{ $dateRangeNetIncome < 0 ? 'text-danger' : 'text-success' }}">
                                                ₦{{ number_format($dateRangeNetIncome, 2) }}
                                            </h4>
                                            <p class="text-muted mb-0">
                                                Net income for {{ $daysCount }} days
                                            </p>
                                            <small>
                                                {{ $dateRangeNetIncome < 0 ? 'Loss' : 'Profit' }} per day: 
                                                ₦{{ number_format($dateRangeNetIncome / $daysCount, 2) }}
                                            </small>
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
                                <strong>Lifetime Operational Expenses:</strong><br>
                                <span class="text-danger">₦{{ number_format($lifetimeOperationalExpenses, 2) }}</span>
                                <small class="text-muted d-block">Feed: ₦{{ number_format($lifetimeFeedCost, 2) }}, Drugs: ₦{{ number_format($lifetimeDrugCost, 2) }}, Labor: ₦{{ number_format($lifetimeLaborCost, 2) }}</small>
                            </div>
                            <div class="mb-3">
                                <strong>Lifetime Net Income:</strong><br>
                                <span class="{{ $lifetimeNetIncome < 0 ? 'text-danger' : 'text-success' }}">
                                    ₦{{ number_format($lifetimeNetIncome, 2) }}
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
                            <h5 class="card-title mb-0">
                                Performance KPIs
                                <span class="badge bg-primary data-type-badge">Lifetime</span>
                                <span class="badge bg-secondary data-type-badge">Date Range</span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 col-6 mb-3">
                                    <div class="text-center">
                                        <div class="text-muted mb-1">Revenue per Bird</div>
                                        <h4>₦{{ number_format($lifetimeRevenuePerBird, 2) }}</h4>
                                        <small>Lifetime</small>
                                        <div class="mt-1">
                                            <small class="text-secondary">Date Range: ₦{{ number_format($dateRangeRevenuePerBird, 2) }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <div class="text-center">
                                        <div class="text-muted mb-1">Feed per Bird</div>
                                        <h4>{{ number_format($lifetimeFeedPerBird, 2) }}</h4>
                                        <small>bags (Lifetime)</small>
                                        <div class="mt-1">
                                            <small class="text-secondary">Date Range: {{ number_format($dateRangeFeedPerBird, 2) }} bags</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <div class="text-center">
                                        <div class="text-muted mb-1">Feed Efficiency</div>
                                        <h4>{{ number_format($lifetimeFeedEfficiency, 2) }}</h4>
                                        <small>bags/crate (Lifetime)</small>
                                        <div class="mt-1">
                                            <small class="text-secondary">Date Range: {{ number_format($dateRangeFeedEfficiency, 2) }} bags/crate</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <div class="text-center">
                                        <div class="text-muted mb-1">Cost per Egg</div>
                                        <h4>₦{{ number_format($lifetimeCostPerEgg, 2) }}</h4>
                                        <small>Lifetime</small>
                                        <div class="mt-1">
                                            <small class="text-secondary">Date Range: ₦{{ number_format($dateRangeCostPerEgg, 2) }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <div class="text-center">
                                        <div class="text-muted mb-1">Avg Daily Production</div>
                                        <h4>{{ number_format($avgDailyProduction, 0) }}</h4>
                                        <small>eggs per day (Date Range)</small>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <div class="text-center">
                                        <div class="text-muted mb-1">Avg Daily Birds</div>
                                        <h4>{{ number_format($avgDailyBirds, 0) }}</h4>
                                        <small>birds per day (Date Range)</small>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <div class="text-center">
                                        <div class="text-muted mb-1">Bird Mortality Rate</div>
                                        <h4>{{ number_format($birdMortalityRate, 1) }}%</h4>
                                        <small>of total flock</small>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <div class="text-center">
                                        <div class="text-muted mb-1">Egg Mortality Rate</div>
                                        <h4>{{ number_format($lifetimeEggMortalityRate, 1) }}%</h4>
                                        <small>Lifetime</small>
                                        <div class="mt-1">
                                            <small class="text-secondary">Date Range: {{ number_format($dateRangeEggMortalityRate, 1) }}%</small>
                                        </div>
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
                            <div class="alert {{ $lifetimeNetIncome < 0 ? 'alert-warning' : 'alert-success' }}">
                                <h5 class="alert-heading">Overall Performance Summary</h5>
                                <p>
                                    @if($flockId)
                                        <strong>Flock {{ $flockId }}</strong> started with <strong>{{ number_format($totalBirds, 0) }} birds</strong> 
                                        and currently has <strong>{{ number_format($currentBirds, 0) }} birds</strong>.
                                    @else
                                        Combined flocks started with <strong>{{ number_format($totalBirds, 0) }} birds</strong> 
                                        and currently have <strong>{{ number_format($currentBirds, 0) }} birds</strong>.
                                    @endif
                                </p>
                                <p>
                                    <strong>Lifetime Production:</strong> 
                                    {{ number_format($lifetimeEggsProduced, 0) }} eggs produced, 
                                    {{ number_format($lifetimeEggsSold, 0) }} eggs sold ({{ number_format($lifetimeEggsSoldCrates, 0) }} Cr {{ $lifetimeEggsSoldPieces }} Pc),
                                    generating <strong>₦{{ number_format($lifetimeRevenue, 2) }}</strong> in revenue.
                                </p>
                                <p>
                                    <strong>Date Range Performance ({{ $daysCount }} days):</strong>
                                    Produced {{ number_format($dateRangeProduction['total_egg_pieces'], 0) }} eggs,
                                    sold {{ number_format($dateRangeProduction['total_sold_pieces'], 0) }} eggs,
                                    generating <strong>₦{{ number_format($dateRangeRevenue, 2) }}</strong> in revenue.
                                </p>
                                <p>
                                    <strong>Key Metrics:</strong>
                                    Bird mortality rate: {{ number_format($birdMortalityRate, 1) }}%,
                                    Egg production rate: {{ number_format($avgProductionRate, 1) }}%,
                                    Feed efficiency: {{ number_format($lifetimeFeedEfficiency, 2) }} bags per crate.
                                </p>
                                <hr>
                                <p class="mb-0">
                                    <strong>Financial Summary:</strong> 
                                    @if($lifetimeNetIncome < 0)
                                        Lifetime operation incurred a loss of <strong>₦{{ number_format(abs($lifetimeNetIncome), 2) }}</strong>.
                                        Date range shows {{ $dateRangeNetIncome < 0 ? 'a loss' : 'profit' }} of <strong>₦{{ number_format(abs($dateRangeNetIncome), 2) }}</strong>.
                                    @else
                                        Lifetime operation generated a profit of <strong>₦{{ number_format($lifetimeNetIncome, 2) }}</strong> 
                                        with a profit margin of {{ $lifetimeRevenue > 0 ? number_format(($lifetimeNetIncome/$lifetimeRevenue)*100, 1) : 0 }}%.
                                        Date range shows {{ $dateRangeNetIncome < 0 ? 'a loss' : 'profit' }} of <strong>₦{{ number_format(abs($dateRangeNetIncome), 2) }}</strong>.
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
                    labels: ['Capital Investment', 'Lifetime Expenses', 'Lifetime Net Income'],
                    datasets: [{
                        data: [
                            Math.max(0, {{ $capitalInvestment }}),
                            Math.max(0, {{ $lifetimeOperationalExpenses }}),
                            Math.max(0, {{ $lifetimeNetIncome }})
                        ],
                        backgroundColor: ['#0d6efd', '#dc3545', '{{ $lifetimeNetIncome >= 0 ? "#28a745" : "#dc3545" }}'],
                        borderColor: ['#0d6efd', '#dc3545', '{{ $lifetimeNetIncome >= 0 ? "#28a745" : "#dc3545" }}'],
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
    });
</script>
@endsection