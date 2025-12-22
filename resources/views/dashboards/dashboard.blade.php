lets confirm other details too to be correct please.
Flock Information
Total Birds (Initial):

1,650
Current Birds:

1,601
Total Mortality:

49
Calculated: 1,650 - 1,601 = 49
Data Quality Summary
Total Days:
28
Avg Daily Birds:
809
Avg Daily Eggs:
743
Data Issues:
None
Total Birds

1,650
Initial count
Current Birds

1,601
Latest count
Bird Mortality

49
3.0% of flock
1,650 - 1,601
Production Rate

47.579,501,258,293%
Average eggs per bird per day
Total Egg Production

693 Cr 7 Pc
20,797 total eggs
Eggs Sold

654 Cr 23 Pc
19,643 total eggs
Egg Mortality (Broken)

219
Broken/damaged eggs
Egg Mortality Rate

1.0,530,364,956,484%
Broken vs Total Production
Feed Consumption

182.5 bags
9,125 kg total
Feed Cost

2,737,500
182.5 bags × ₦15,000
Total Revenue

1,964,300
19,643 eggs × ₦100
Net Income

-1,128,199.9,999,999
Loss
Feed Consumption - Last 4 Weeks
Feed consumption in bags
Drug Usage - Treatment Days
Number of days with medication administered
Egg Production vs. Sold
Comparison of eggs produced vs eggs sold
Production Rate & Egg Mortality
Production rate (%) vs Broken eggs count
Financial Breakdown
Income
Egg Sales (19,643 eggs):
₦1,964,300.00
Total Income:
₦1,964,300.00
Expenses
Feed Cost (182.5 bags @ ₦15,000):
₦2,737,500.00
Drug Cost (9 days @ ₦5,000):
₦45,000.00
Labor Cost (30.999999999988 days @ ₦10,000):
₦310,000.00
Total Expenses:
₦3,092,500.00
Net Income: ₦-1,128,200.00
Operating at a loss of ₦1,128,200.00

Flock Capital Analysis
Capital Investment:
₦3,300,000.00
1,650 birds × ₦2,000 each
Operational Expenses:
₦3,092,500.00
Feed: ₦2,737,500.00, Drugs: ₦45,000.00, Labor: ₦310,000.00
Net Income:
₦-1,128,200.00
Capital Value:
₦0.00
Based on income approach (10% capitalization rate)
Summary Report
Overall Performance Summary
During the selected period (Nov 22, 2025 to Dec 22, 2025), all flocks combined started with 1,650 birds and currently has 1,601 birds.

Mortality: 49 birds (3.0% of flock).

The flock produced 20,797 eggs (693 crates 7 pieces).

Of these, 19,643 eggs were sold, generating ₦1,964,300.00 in revenue. 219 eggs were broken (1.1% of production).

Feed consumption totaled 182.5 bags costing ₦2,737,500.00. Medication was administered on 9 days costing ₦45,000.00.

Final Result: The operation incurred a loss of ₦1,128,200.00 during this period. Consider reviewing feed efficiency and mortality rates.

Efficiency Metrics
Feed per Bird:
0.11 bags/bird
Feed Efficiency:
0.2633 bags/egg
Revenue per Bird:
₦1,226.92
Cost per Egg:
₦157.44
Sales & Disposal Metrics
Egg Sales Efficiency:
98.9%
Egg Disposal Rate:
95.5%
Avg Production Rate:
47.6%
Bird Mortality Rate:
3.0%


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
    
    /* Currency styling */
    .currency {
        font-family: Arial, sans-serif;
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

            <!-- Flock Info Summary -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">Flock Information</h6>
                            <div class="row">
                                <div class="col-6">
                                    <p class="mb-1"><strong>Total Birds (Initial):</strong></p>
                                    <h4 class="text-primary">{{ number_format($totalBirds, 0) }}</h4>
                                </div>
                                <div class="col-6">
                                    <p class="mb-1"><strong>Current Birds:</strong></p>
                                    <h4 class="text-success">{{ number_format($currentBirds, 0) }}</h4>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <p class="mb-1"><strong>Total Mortality:</strong></p>
                                    <h4 class="text-danger">{{ number_format($totalMortality, 0) }}</h4>
                                    <small class="text-muted">Calculated: {{ number_format($totalBirds, 0) }} - {{ number_format($currentBirds, 0) }} = {{ number_format($totalMortality, 0) }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">Data Quality Summary</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Days:</span>
                                <strong>{{ $daysWithProduction }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Avg Daily Birds:</span>
                                <strong>{{ number_format($avgDailyBirds, 0) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Avg Daily Eggs:</span>
                                <strong>{{ number_format($avgDailyProduction, 0) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Data Issues:</span>
                                <strong class="{{ $hasDataQualityIssues ? 'text-warning' : 'text-success' }}">
                                    {{ $hasDataQualityIssues ? count($unrealisticEntries) . ' entries' : 'None' }}
                                </strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- First Row - Key Metrics -->
            <div class="row">
                <!-- Total Birds Card -->
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

                <!-- Current Birds Card -->
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

                <!-- Bird Mortality Card - FIXED -->
                <div class="col-xxl-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="fs-md text-muted mb-4">Bird Mortality</p>
                                    <h3 class="mb-0 mt-auto"><span class="counter-value" data-target="{{ $totalMortality }}">{{ number_format($totalMortality, 0) }}</span></h3>
                                    <small class="text-muted">
                                        {{ number_format($birdMortalityRate, 1) }}% of flock
                                        <br>{{ number_format($totalBirds, 0) }} - {{ number_format($currentBirds, 0) }}
                                    </small>
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

                <!-- Production Rate Card - FIXED -->
                <div class="col-xxl-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="fs-md text-muted mb-4">Production Rate</p>
                                    <h3 class="mb-0 mt-auto"><span class="counter-value" data-target="{{ $avgProductionRate }}">{{ number_format($avgProductionRate, 1) }}</span>%</h3>
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

            <!-- Second Row - Egg Production Metrics -->
            <div class="row">
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

                <!-- Egg Mortality Card -->
                <div class="col-xxl-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="fs-md text-muted mb-4">Egg Mortality (Broken)</p>
                                    <h3 class="mb-0 mt-auto"><span class="counter-value" data-target="{{ $totalEggMortality }}">{{ number_format($totalEggMortality, 0) }}</span></h3>
                                    <small class="text-muted">Broken/damaged eggs</small>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm">
                                        <span class="avatar-title bg-warning-subtle text-warning rounded fs-3">
                                            <i class="bi bi-x-circle"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Egg Mortality Rate Card - FIXED -->
                <div class="col-xxl-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="fs-md text-muted mb-4">Egg Mortality Rate</p>
                                    <h3 class="mb-0 mt-auto"><span class="counter-value" data-target="{{ $eggMortalityRate }}">{{ number_format($eggMortalityRate, 1) }}</span>%</h3>
                                    <small class="text-muted">Broken vs Total Production</small>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm">
                                        <span class="avatar-title bg-danger-subtle text-danger rounded fs-3">
                                            <i class="bi bi-pie-chart"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Third Row - Feed & Cost Metrics -->
            <div class="row">
                <!-- Feed Consumption Card -->
                <div class="col-xxl-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="fs-md text-muted mb-4">Feed Consumption</p>
                                    <h3 class="mb-0 mt-auto">
                                        <span class="counter-value" data-target="{{ $totalFeedBags }}">
                                            {{ number_format($totalFeedBags, 1) }}
                                        </span> bags
                                    </h3>
                                    <small class="text-muted">{{ number_format($totalFeedKg, 0) }} kg total</small>
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

                <!-- Feed Cost Card - UPDATED TO NAIRA -->
                <div class="col-xxl-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="fs-md text-muted mb-4">Feed Cost</p>
                                    <h3 class="mb-0 mt-auto currency"><span class="counter-value" data-target="{{ $feedCost }}">₦{{ number_format($feedCost, 2) }}</span></h3>
                                    <small class="text-muted">{{ number_format($totalFeedBags, 1) }} bags × ₦15,000</small>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm">
                                        <span class="avatar-title bg-danger-subtle text-danger rounded fs-3">
                                            <i class="bi bi-currency-exchange"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenue Card - UPDATED TO NAIRA -->
                <div class="col-xxl-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="fs-md text-muted mb-4">Total Revenue</p>
                                    <h3 class="mb-0 mt-auto currency"><span class="counter-value" data-target="{{ $totalRevenue }}">₦{{ number_format($totalRevenue, 2) }}</span></h3>
                                    <small class="text-muted">{{ number_format($totalEggsSoldTotalPieces, 0) }} eggs × ₦100</small>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm">
                                        <span class="avatar-title bg-success-subtle text-success rounded fs-3">
                                            <i class="bi bi-cash-stack"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Net Income Card - UPDATED TO NAIRA -->
                <div class="col-xxl-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <p class="fs-md text-muted mb-4">Net Income</p>
                                    <h3 class="mb-0 mt-auto currency {{ $netIncome >= 0 ? 'text-success' : 'text-danger' }}">
                                        <span class="counter-value" data-target="{{ $netIncome }}">₦{{ number_format($netIncome, 2) }}</span>
                                    </h3>
                                    <small class="text-muted">
                                        @if($netIncome >= 0)
                                            {{ $totalRevenue > 0 ? number_format(($netIncome/$totalRevenue)*100, 1) : 0 }}% profit margin
                                        @else
                                            Loss
                                        @endif
                                    </small>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm">
                                        <span class="avatar-title {{ $netIncome >= 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} rounded fs-3">
                                            <i class="bi bi-graph-up-arrow"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section - FIXED CONTAINER -->
            <div class="row">
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
                                <small class="text-muted">Feed consumption in bags</small>
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
                                <small class="text-muted">Number of days with medication administered</small>
                            </div>
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

            <!-- Financial Analysis Section - UPDATED TO NAIRA -->
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
                                                <span>Egg Sales ({{ number_format($totalEggsSoldTotalPieces, 0) }} eggs):</span>
                                                <strong class="text-success currency">₦{{ number_format($totalRevenue, 2) }}</strong>
                                            </div>
                                            <hr>
                                            <div class="d-flex justify-content-between">
                                                <span>Total Income:</span>
                                                <strong class="text-success currency">₦{{ number_format($totalRevenue, 2) }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title">Expenses</h6>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Feed Cost ({{ number_format($totalFeedBags, 1) }} bags @ ₦15,000):</span>
                                                <strong class="text-danger currency">₦{{ number_format($feedCost, 2) }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Drug Cost ({{ $totalDrugUsage }} days @ ₦5,000):</span>
                                                <strong class="text-danger currency">₦{{ number_format($drugCost, 2) }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Labor Cost ({{ $startDate->diffInDays($endDate) ?: 30 }} days @ ₦10,000):</span>
                                                <strong class="text-danger currency">₦{{ number_format($laborCost, 2) }}</strong>
                                            </div>
                                            <hr>
                                            <div class="d-flex justify-content-between">
                                                <span>Total Expenses:</span>
                                                <strong class="text-danger currency">₦{{ number_format($operationalExpenses, 2) }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="card {{ $netIncome < 0 ? 'bg-danger-subtle' : 'bg-success-subtle' }}">
                                        <div class="card-body text-center">
                                            <h4 class="{{ $netIncome < 0 ? 'text-danger' : 'text-success' }} currency">
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
                                <span class="text-primary currency">₦{{ number_format($capitalInvestment, 2) }}</span>
                                <small class="text-muted d-block">{{ number_format($totalBirds, 0) }} birds × ₦2,000 each</small>
                            </div>
                            <div class="mb-3">
                                <strong>Operational Expenses:</strong><br>
                                <span class="text-danger currency">₦{{ number_format($operationalExpenses, 2) }}</span>
                                <small class="text-muted d-block">
                                    Feed: ₦{{ number_format($feedCost, 2) }}, 
                                    Drugs: ₦{{ number_format($drugCost, 2) }}, 
                                    Labor: ₦{{ number_format($laborCost, 2) }}
                                </small>
                            </div>
                            <div class="mb-3">
                                <strong>Net Income:</strong><br>
                                <span class="{{ $netIncome < 0 ? 'text-danger' : 'text-success' }} currency">
                                    ₦{{ number_format($netIncome, 2) }}
                                </span>
                            </div>
                            <div class="mb-3">
                                <strong>Capital Value:</strong><br>
                                <span class="text-info currency">₦{{ number_format($capitalValue, 2) }}</span>
                                <small class="text-muted d-block">Based on income approach (10% capitalization rate)</small>
                            </div>
                            <div class="chart-container" style="height: 250px;">
                                <canvas id="flockCapitalChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Section - UPDATED TO NAIRA -->
            <div class="row">
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
                                        all flocks combined
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
                                    generating <strong class="currency">₦{{ number_format($totalRevenue, 2) }}</strong> in revenue. 
                                    <strong>{{ number_format($totalEggMortality, 0) }} eggs</strong> were broken ({{ number_format($eggMortalityRate, 1) }}% of production).
                                </p>
                                <p>
                                    Feed consumption totaled <strong>{{ number_format($totalFeedBags, 1) }} bags</strong> 
                                    costing <strong class="currency">₦{{ number_format($feedCost, 2) }}</strong>.
                                    Medication was administered on <strong>{{ $totalDrugUsage }} days</strong> costing <strong class="currency">₦{{ number_format($drugCost, 2) }}</strong>.
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
                                        The operation incurred a loss of <strong class="currency">₦{{ number_format(abs($netIncome), 2) }}</strong> 
                                        during this period. Consider reviewing feed efficiency and mortality rates.
                                    @else
                                        The operation generated a profit of <strong class="currency">₦{{ number_format($netIncome, 2) }}</strong> 
                                        with a profit margin of {{ $totalRevenue > 0 ? number_format(($netIncome/$totalRevenue)*100, 1) : 0 }}%.
                                    @endif
                                </p>
                            </div>
                            
                            <!-- Additional Metrics -->
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6 class="card-title">Efficiency Metrics</h6>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Feed per Bird:</span>
                                                <strong>{{ number_format($feedPerBird, 2) }} bags/bird</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Feed Efficiency:</span>
                                                <strong>{{ number_format($feedEfficiency, 4) }} bags/egg</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Revenue per Bird:</span>
                                                <strong class="currency">₦{{ number_format($revenuePerBird, 2) }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>Cost per Egg:</span>
                                                <strong class="currency">₦{{ number_format($costPerEgg, 2) }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6 class="card-title">Sales & Disposal Metrics</h6>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Egg Sales Efficiency:</span>
                                                <strong>{{ number_format($eggSalesEfficiency, 1) }}%</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Egg Disposal Rate:</span>
                                                <strong>{{ number_format($eggDisposalRate, 1) }}%</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Avg Production Rate:</span>
                                                <strong>{{ number_format($avgProductionRate, 1) }}%</strong>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>Bird Mortality Rate:</span>
                                                <strong>{{ number_format($birdMortalityRate, 1) }}%</strong>
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
                    decimalPlaces: element.textContent.includes('₦') ? 2 : 
                                  element.textContent.includes('%') ? 1 : 
                                  element.textContent.includes('bags') ? 1 : 0
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

        // Initialize Feed Consumption Chart (in BAGS)
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
                    labels: {!! json_encode($weeks->map(function($week) {
                        return 'Week ' . substr($week, -2);
                    })) !!},
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
                    labels: {!! json_encode($weeks->map(function($week) {
                        return 'Week ' . substr($week, -2);
                    })) !!},
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

        // Initialize Flock Capital Chart - UPDATED TO NAIRA
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
    });
</script>
@endsection