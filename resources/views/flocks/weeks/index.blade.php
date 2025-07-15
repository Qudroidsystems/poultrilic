@extends('layouts.master')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Week Entries for {{ $flock->initial_bird_count }} Capital Birds</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Flock Management</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('flocks.index') }}">Flocks</a></li>
                                <li class="breadcrumb-item active">Week Entries</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End page title -->

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Whoops!</strong> There were some problems with your input.<br><br>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('status') || session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('status') ?? session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div id="weekList">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3 align-items-center">
                                    <div class="col-md-4">
                                        <div class="search-box">
                                            <input type="text" class="form-control search" id="searchWeek" placeholder="Search by week name" aria-label="Search week entries">
                                            <i class="ri-search-line search-icon"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <select id="weekFilter" class="form-control" data-choices data-choices-search-true data-choices-removeItem>
                                            <option value="all">Select Week Number Range</option>
                                            <option value="1-10">1-10</option>
                                            <option value="11-20">11-20</option>
                                            <option value="21-30">21-30</option>
                                            <option value="31+">31+</option>
                                        </select>
                                    </div>
                                    <div class="col-md-auto">
                                        <button type="button" class="btn btn-secondary w-100" onclick="filterData();"><i class="bi bi-funnel align-baseline me-1"></i> Filter</button>
                                    </div>
                                    <div class="col-md-auto ms-auto">
                                        <div class="hstack gap-2">
                                            @can('Create weekly-entry')
                                                <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#addWeekModal"><i class="bi bi-plus-circle align-baseline me-1"></i> Add Week Entry</button>
                                            @endcan
                                            <button class="btn btn-outline-danger d-none" id="remove-actions" onclick="deleteMultiple()"><i class="ri-delete-bin-2-line"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center">
                                <h5 class="card-title mb-0 flex-grow-1">Week Entries <span class="badge bg-dark-subtle text-dark ms-1">{{ $weekEntries->total() }}</span></h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-centered align-middle table-nowrap mb-0" id="weekTable">
                                        <thead class="table-active">
                                            <tr>
                                                <th scope="col">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkAll" value="option">
                                                        <label class="form-check-label"></label>
                                                    </div>
                                                </th>
                                                <th scope="col">Week Name</th>
                                                <th scope="col">Daily Entries</th>
                                                <th scope="col">Created At</th>
                                                <th scope="col">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="list form-check-all">
                                            @forelse ($weekEntries as $week)
                                                <tr key="{{ $week->id }}">
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input chk-child" type="checkbox" name="chk_child" value="{{ $week->id }}" data-id="{{ $week->id }}">
                                                            <label class="form-check-label"></label>
                                                        </div>
                                                    </td>
                                                    <td class="week_name">{{ $week->week_name }}</td>
                                                    <td class="daily_entries_count">{{ $week->daily_entries_count }}</td>
                                                    <td class="created_at">{{ $week->created_at->format('Y-m-d') }}</td>
                                                    <td>
                                                        <div class="hstack gap-2">
                                                            @can('View weekly-entry')
                                                                <a href="{{ route('daily-entries.index', $week->id) }}" class="btn btn-subtle-primary btn-icon btn-sm" title="View daily entries"><i class="ph-eye"></i></a>
                                                            @endcan
                                                            @can('Update weekly-entry')
                                                                <button type="button" class="btn btn-subtle-secondary btn-icon btn-sm edit-item-btn" title="Edit week"><i class="ph-pencil"></i></button>
                                                            @endcan
                                                            @can('Delete weekly-entry')
                                                                <button type="button" class="btn btn-subtle-danger btn-icon btn-sm remove-item-btn" title="Delete week"><i class="ph-trash"></i></button>
                                                            @endcan
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr class="noresult">
                                                    <td colspan="5" class="text-center">No weeks found</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                    <!-- Template for List.js new items -->
                                    <template id="weekRowTemplate">
                                        <tr>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input chk-child" type="checkbox" name="chk_child" value="{id}" data-id="{id}">
                                                    <label class="form-check-label"></label>
                                                </div>
                                            </td>
                                            <td class="week_name">{week_name}</td>
                                            <td class="daily_entries_count">{daily_entries_count}</td>
                                            <td class="created_at">{created_at}</td>
                                            <td>
                                                <div class="hstack gap-2">
                                                    <a href="/daily-entries/{id}" class="btn btn-subtle-primary btn-icon btn-sm" title="View daily entries"><i class="ph-eye"></i></a>
                                                    <button type="button" class="btn btn-subtle-secondary btn-icon btn-sm edit-item-btn" title="Edit week"><i class="ph-pencil"></i></button>
                                                    <button type="button" class="btn btn-subtle-danger btn-icon btn-sm remove-item-btn" title="Delete week"><i class="ph-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </div>
                                <div class="row mt-3 align-items-center" id="pagination-element">
                                    <div class="col-sm">
                                        <div class="text-muted text-center text-sm-start">
                                            Showing <span class="fw-semibold">{{ $weekEntries->count() }}</span> of <span class="fw-semibold">{{ $weekEntries->total() }}</span> Results
                                        </div>
                                    </div>
                                    <div class="col-sm-auto mt-3 mt-sm-0">
                                        <div class="pagination-wrap hstack gap-2 justify-content-center">
                                            {{ $weekEntries->links() }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts and Statistics -->
                <div class="row mt-4">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Week Entries Chart</h5>
                                <canvas id="weekChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Daily Entries Chart</h5>
                                <canvas id="dailyEntryChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-body" id="week-stats">
                                <p class="text-muted">Select a week to view statistics.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-body" id="all-weeks-stats">
                                @if (isset($allWeeksStats))
                                    <h5 class="card-title">All Weeks Statistics</h5>
                                    <p>Total Daily Entries: {{ $allWeeksStats['total_daily_entries'] }}</p>
                                    <p>Total Egg Production: {{ $allWeeksStats['total_egg_production'] }} eggs</p>
                                    <p>Total Mortality: {{ $allWeeksStats['total_mortality'] }} birds</p>
                                    <p>Total Feeds Consumed: {{ $allWeeksStats['total_feeds_consumed'] }} kg</p>
                                    <p>Average Daily Egg Production: {{ number_format($allWeeksStats['avg_daily_egg_production'], 2) }} eggs</p>
                                    <p>Average Daily Mortality: {{ number_format($allWeeksStats['avg_daily_mortality'], 2) }} birds</p>
                                    <p>Average Daily Feeds: {{ number_format($allWeeksStats['avg_daily_feeds'], 2) }} kg</p>
                                @else
                                    <p class="text-muted">No statistics available.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Week Modal -->
            <div id="addWeekModal" class="modal fade" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 id="addModalLabel" class="modal-title">Add Week</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form class="tablelist-form" autocomplete="off" id="add-week-form">
                            @csrf
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="week_number" class="form-label">Week Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Week</span>
                                        <input type="number" id="week_number" name="week_number" class="form-control" placeholder="Enter week number" required min="1">
                                    </div>
                                    <div class="alert alert-danger d-none" id="add-error-msg"></div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" id="add-btn">Add Week</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Week Modal -->
            <div id="editWeekModal" class="modal fade" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 id="editModalLabel" class="modal-title">Edit Week</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form class="tablelist-form" autocomplete="off" id="edit-week-form">
                            @csrf
                            @method('PUT')
                            <div class="modal-body">
                                <input type="hidden" id="edit-id-field" name="id">
                                <div class="mb-3">
                                    <label for="edit_week_number" class="form-label">Week Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Week</span>
                                        <input type="number" id="edit_week_number" name="week_number" class="form-control" placeholder="Enter week number" required min="1">
                                    </div>
                                    <div class="alert alert-danger d-none" id="edit-error-msg"></div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" id="update-btn">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Delete Week Modal -->
            <div id="deleteRecordModal" class="modal fade zoomIn" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="btn-close" id="deleteRecord-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-md-5">
                            <div class="text-center">
                                <div class="text-danger">
                                    <i class="bi bi-trash display-4"></i>
                                </div>
                                <div class="mt-4">
                                    <h3 class="mb-2">Are you sure?</h3>
                                    <p class="text-muted fs-lg mx-3 mb-0">Are you sure you want to remove this week?</p>
                                </div>
                            </div>
                            <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                                <button type="button" class="btn w-sm btn-light btn-hover" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn w-sm btn-danger btn-hover" id="delete-record">Delete</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
        <script>
            window.flockId = {{ $flock->id }};

            // Initialize Week Chart
            const ctx = document.getElementById('weekChart').getContext('2d');
            window.weekChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: @json($chartData['labels']),
                    datasets: [{
                        label: 'Daily Entries',
                        data: @json($chartData['daily_entries_counts']),
                        backgroundColor: '#0d6efd',
                        borderColor: '#0d6efd',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true, title: { display: true, text: 'Number of Daily Entries' } },
                        x: { title: { display: true, text: 'Week' } }
                    },
                    plugins: {
                        legend: { display: true },
                        title: { display: true, text: 'Week Entries Overview' }
                    }
                }
            });
        </script>
    </div>
</div>
@endsection