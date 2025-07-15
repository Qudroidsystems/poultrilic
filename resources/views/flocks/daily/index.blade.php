@extends('layouts.master')

@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Daily Entries for {{ $week->week_name }} (Flock {{ $flock->initial_bird_count }} Birds)</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Flock Management</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('flocks.index') }}">Flocks</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('week-entries.index', $flock->id) }}">Week Entries</a></li>
                                <li class="breadcrumb-item active">Daily Entries</li>
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

            <div id="dailyList">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3 align-items-center">
                                    <div class="col-md-4">
                                        <div class="search-box">
                                            <input type="text" class="form-control search" id="searchDay" placeholder="Search by day number" aria-label="Search daily entries">
                                            <i class="ri-search-line search-icon"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <select id="dayFilter" class="form-control" data-choices data-choices-search-true data-choices-removeItem>
                                            <option value="all">Select Day Number</option>
                                            <option value="1">Day 1</option>
                                            <option value="2">Day 2</option>
                                            <option value="3">Day 3</option>
                                            <option value="4">Day 4</option>
                                            <option value="5">Day 5</option>
                                            <option value="6">Day 6</option>
                                            <option value="7">Day 7</option>
                                        </select>
                                    </div>
                                    <div class="col-md-auto">
                                        <button type="button" class="btn btn-secondary w-100" onclick="filterData();"><i class="bi bi-funnel align-baseline me-1"></i> Filter</button>
                                    </div>
                                    <div class="col-md-auto ms-auto">
                                        <div class="hstack gap-2">
                                            @can('Create daily-entry')
                                                <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#addDailyModal"><i class="bi bi-plus-circle align-baseline me-1"></i> Add Daily Entry</button>
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
                                <h5 class="card-title mb-0 flex-grow-1">Daily Entries <span class="badge bg-dark-subtle text-dark ms-1">{{ $dailyEntries->total() }}</span></h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-centered align-middle table-nowrap mb-0" id="dailyTable">
                                        <thead class="table-active">
                                            <tr>
                                                <th scope="col">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkAll" value="option">
                                                        <label class="form-check-label"></label>
                                                    </div>
                                                </th>
                                                <th scope="col">Day Number</th>
                                                <th scope="col">Daily Feeds</th>
                                                <th scope="col">Daily Mortality</th>
                                                <th scope="col">Current Birds</th>
                                                <th scope="col">Daily Egg Production</th>
                                                <th scope="col">Daily Sold Eggs</th>
                                                <th scope="col">Broken Eggs</th>
                                                <th scope="col">Outstanding Eggs</th>
                                                <th scope="col">Total Eggs in Farm</th>
                                                <th scope="col">Created At</th>
                                                <th scope="col">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="list form-check-all">
                                            @forelse ($dailyEntries as $entry)
                                                <tr key="{{ $entry->id }}">
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input chk-child" type="checkbox" name="chk_child" value="{{ $entry->id }}" data-id="{{ $entry->id }}">
                                                            <label class="form-check-label"></label>
                                                        </div>
                                                    </td>
                                                    <td class="day_number">Day {{ $entry->day_number }}</td>
                                                    <td class="daily_feeds">{{ $entry->daily_feeds }}</td>
                                                    <td class="daily_mortality">{{ $entry->daily_mortality }}</td>
                                                    <td class="current_birds">{{ $entry->current_birds }}</td>
                                                    <td class="daily_egg_production">
                                                        {{ floor($entry->daily_egg_production / 30) }} cr {{ $entry->daily_egg_production % 30 }} pcs ({{ $entry->daily_egg_production }} pieces)
                                                    </td>
                                                    <td class="daily_sold_egg">
                                                        {{ floor($entry->daily_sold_egg / 30) }} cr {{ $entry->daily_sold_egg % 30 }} pcs ({{ $entry->daily_sold_egg }} pieces)
                                                    </td>
                                                    <td class="broken_egg">
                                                        {{ floor($entry->broken_egg / 30) }} cr {{ $entry->broken_egg % 30 }} pcs ({{ $entry->broken_egg }} pieces)
                                                    </td>
                                                    <td class="outstanding_egg">
                                                        {{ floor($entry->outstanding_egg / 30) }} cr {{ $entry->outstanding_egg % 30 }} pcs ({{ $entry->outstanding_egg }} pieces)
                                                    </td>
                                                    <td class="total_egg_in_farm">
                                                        {{ floor($entry->total_egg_in_farm / 30) }} cr {{ $entry->total_egg_in_farm % 30 }} pcs ({{ $entry->total_egg_in_farm }} pieces)
                                                    </td>
                                                    <td class="created_at">{{ $entry->created_at->format('Y-m-d') }}</td>
                                                    <td>
                                                        <div class="hstack gap-2">
                                                            @can('Update daily-entry')
                                                                <button type="button" class="btn btn-subtle-secondary btn-icon btn-sm edit-item-btn" title="Edit entry"><i class="ph-pencil"></i></button>
                                                            @endcan
                                                            @can('Delete daily-entry')
                                                                <button type="button" class="btn btn-subtle-danger btn-icon btn-sm remove-item-btn" title="Delete entry"><i class="ph-trash"></i></button>
                                                            @endcan
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr class="noresult">
                                                    <td colspan="11" class="text-center">No daily entries found</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                    <!-- Template for List.js new items -->
                                    <template id="dailyRowTemplate">
                                        <tr>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input chk-child" type="checkbox" name="chk_child" value="{id}" data-id="{id}">
                                                    <label class="form-check-label"></label>
                                                </div>
                                            </td>
                                            <td class="day_number">{day_number}</td>
                                            <td class="daily_feeds">{daily_feeds}</td>
                                            <td class="daily_mortality">{daily_mortality}</td>
                                            <td class="current_birds">{current_birds}</td>
                                            <td class="daily_egg_production">{daily_egg_production}</td>
                                            <td class="daily_sold_egg">{daily_sold_egg}</td>
                                            <td class="broken_egg">{broken_egg}</td>
                                            <td class="outstanding_egg">{outstanding_egg}</td>
                                            <td class="total_egg_in_farm">{total_egg_in_farm}</td>
                                            <td class="created_at">{created_at}</td>
                                            <td>
                                                <div class="hstack gap-2">
                                                    <button type="button" class="btn btn-subtle-secondary btn-icon btn-sm edit-item-btn" title="Edit entry"><i class="ph-pencil"></i></button>
                                                    <button type="button" class="btn btn-subtle-danger btn-icon btn-sm remove-item-btn" title="Delete entry"><i class="ph-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </div>
                                <div class="row mt-3 align-items-center" id="pagination-element">
                                    <div class="col-sm">
                                        <div class="text-muted text-center text-sm-start">
                                            Showing <span class="fw-semibold">{{ $dailyEntries->count() }}</span> of <span class="fw-semibold">{{ $dailyEntries->total() }}</span> Results
                                        </div>
                                    </div>
                                    <div class="col-sm-auto mt-3 mt-sm-0">
                                        <div class="pagination-wrap hstack gap-2 justify-content-center">
                                            <a class="page-item pagination-prev {{ $dailyEntries->onFirstPage() ? 'disabled' : '' }}" href="{{ $dailyEntries->previousPageUrl() }}">
                                                <i class="mdi mdi-chevron-left align-middle"></i>
                                            </a>
                                            <ul class="pagination listjs-pagination mb-0">
                                                @foreach ($dailyEntries->links()->elements[0] as $page => $url)
                                                    <li class="page-item {{ $dailyEntries->currentPage() == $page ? 'active' : '' }}">
                                                        <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                            <a class="page-item pagination-next {{ $dailyEntries->hasMorePages() ? '' : 'disabled' }}" href="{{ $dailyEntries->nextPageUrl() }}">
                                                <i class="mdi mdi-chevron-right align-middle"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <canvas id="dailyChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Daily Modal -->
            <div id="addDailyModal" class="modal fade" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 id="addModalLabel" class="modal-title">Add Daily Entry</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form class="tablelist-form" autocomplete="off" id="add-daily-form">
                            @csrf
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="day_number" class="form-label">Day Number</label>
                                    <select id="day_number" name="day_number" class="form-control" required>
                                        <option value="">Select Day</option>
                                        @for ($i = 1; $i <= 7; $i++)
                                            <option value="{{ $i }}">Day {{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="daily_feeds" class="form-label">Daily Feeds (kg)</label>
                                    <input type="number" id="daily_feeds" name="daily_feeds" class="form-control" placeholder="Enter daily feeds in kg" required min="0" step="0.01">
                                </div>
                                <div class="mb-3">
                                    <label for="available_feeds" class="form-label">Available Feeds (kg)</label>
                                    <input type="number" id="available_feeds" name="available_feeds" class="form-control" placeholder="Enter available feeds in kg" required min="0" step="0.01">
                                </div>
                                <div class="mb-3">
                                    <label for="daily_mortality" class="form-label">Daily Mortality</label>
                                    <input type="number" id="daily_mortality" name="daily_mortality" class="form-control" placeholder="Enter number of deaths" required min="0">
                                </div>
                                <div class="mb-3">
                                    <label for="sick_bay" class="form-label">Sick Bay</label>
                                    <input type="number" id="sick_bay" name="sick_bay" class="form-control" placeholder="Enter number of sick birds" required min="0">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Daily Egg Production (1 crate = 30 eggs)</label>
                                    <div class="row">
                                        <div class="col">
                                            <input type="number" id="daily_egg_production_crates" name="daily_egg_production_crates" class="form-control" placeholder="Crates" required min="0">
                                        </div>
                                        <div class="col">
                                            <input type="number" id="daily_egg_production_pieces" name="daily_egg_production_pieces" class="form-control" placeholder="Pieces (0-29)" required min="0" max="29">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Daily Sold Eggs (1 crate = 30 eggs)</label>
                                    <div class="row">
                                        <div class="col">
                                            <input type="number" id="daily_sold_egg_crates" name="daily_sold_egg_crates" class="form-control" placeholder="Crates" required min="0">
                                        </div>
                                        <div class="col">
                                            <input type="number" id="daily_sold_egg_pieces" name="daily_sold_egg_pieces" class="form-control" placeholder="Pieces (0-29)" required min="0" max="29">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Broken Eggs (1 crate = 30 eggs)</label>
                                    <div class="row">
                                        <div class="col">
                                            <input type="number" id="broken_egg_crates" name="broken_egg_crates" class="form-control" placeholder="Crates" required min="0">
                                        </div>
                                        <div class="col">
                                            <input type="number" id="broken_egg_pieces" name="broken_egg_pieces" class="form-control" placeholder="Pieces (0-29)" required min="0" max="29">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Drugs Administered</label>
                                    <input type="text" id="drugs" name="drugs" class="form-control" placeholder="Enter drugs administered (optional)">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Reorder Feeds (kg)</label>
                                    <input type="number" id="reorder_feeds" name="reorder_feeds" class="form-control" placeholder="Enter reorder feeds in kg (optional)" min="0" step="0.01">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Total Eggs in Farm</label>
                                    <p class="form-text">Calculated by system</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Outstanding Eggs</label>
                                    <p class="form-text">Calculated by system</p>
                                </div>
                                <div class="alert alert-danger d-none" id="add-error-msg"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" id="add-btn">Add Entry</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Daily Modal -->
            <div id="editDailyModal" class="modal fade" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 id="editModalLabel" class="modal-title">Edit Daily Entry</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form class="tablelist-form" autocomplete="off" id="edit-daily-form">
                            @csrf
                            @method('PUT')
                            <div class="modal-body">
                                <input type="hidden" id="edit-id-field" name="id">
                                <div class="mb-3">
                                    <label for="edit_day_number" class="form-label">Day Number</label>
                                    <select id="edit_day_number" name="day_number" class="form-control" required>
                                        <option value="">Select Day</option>
                                        @for ($i = 1; $i <= 7; $i++)
                                            <option value="{{ $i }}">Day {{ $i }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_daily_feeds" class="form-label">Daily Feeds (kg)</label>
                                    <input type="number" id="edit_daily_feeds" name="daily_feeds" class="form-control" placeholder="Enter daily feeds in kg" required min="0" step="0.01">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_available_feeds" class="form-label">Available Feeds (kg)</label>
                                    <input type="number" id="edit_available_feeds" name="available_feeds" class="form-control" placeholder="Enter available feeds in kg" required min="0" step="0.01">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_daily_mortality" class="form-label">Daily Mortality</label>
                                    <input type="number" id="edit_daily_mortality" name="daily_mortality" class="form-control" placeholder="Enter number of deaths" required min="0">
                                </div>
                                <div class="mb-3">
                                    <label for="edit_sick_bay" class="form-label">Sick Bay</label>
                                    <input type="number" id="edit_sick_bay" name="sick_bay" class="form-control" placeholder="Enter number of sick birds" required min="0">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Daily Egg Production (1 crate = 30 eggs)</label>
                                    <div class="row">
                                        <div class="col">
                                            <input type="number" id="edit_daily_egg_production_crates" name="daily_egg_production_crates" class="form-control" placeholder="Crates" required min="0">
                                        </div>
                                        <div class="col">
                                            <input type="number" id="edit_daily_egg_production_pieces" name="daily_egg_production_pieces" class="form-control" placeholder="Pieces (0-29)" required min="0" max="29">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Daily Sold Eggs (1 crate = 30 eggs)</label>
                                    <div class="row">
                                        <div class="col">
                                            <input type="number" id="edit_daily_sold_egg_crates" name="daily_sold_egg_crates" class="form-control" placeholder="Crates" required min="0">
                                        </div>
                                        <div class="col">
                                            <input type="number" id="edit_daily_sold_egg_pieces" name="daily_sold_egg_pieces" class="form-control" placeholder="Pieces (0-29)" required min="0" max="29">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Broken Eggs (1 crate = 30 eggs)</label>
                                    <div class="row">
                                        <div class="col">
                                            <input type="number" id="edit_broken_egg_crates" name="broken_egg_crates" class="form-control" placeholder="Crates" required min="0">
                                        </div>
                                        <div class="col">
                                            <input type="number" id="edit_broken_egg_pieces" name="broken_egg_pieces" class="form-control" placeholder="Pieces (0-29)" required min="0" max="29">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Drugs Administered</label>
                                    <input type="text" id="edit_drugs" name="drugs" class="form-control" placeholder="Enter drugs administered (optional)">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Reorder Feeds (kg)</label>
                                    <input type="number" id="edit_reorder_feeds" name="reorder_feeds" class="form-control" placeholder="Enter reorder feeds in kg (optional)" min="0" step="0.01">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Total Eggs in Farm</label>
                                    <p class="form-text" id="edit_total_egg_in_farm">Calculated by system</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Outstanding Eggs</label>
                                    <p class="form-text" id="edit_outstanding_egg">Calculated by system</p>
                                </div>
                                <div class="alert alert-danger d-none" id="edit-error-msg"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" id="update-btn">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Delete Daily Modal -->
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
                                    <p class="text-muted fs-lg mx-3 mb-0">Are you sure you want to remove this daily entry?</p>
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
            window.weekId = {{ $week->id }};
            const CRATE_SIZE = 30;

            // Helper function to convert between pieces and crates/pieces
            function toCratesAndPieces(totalPieces) {
                const crates = Math.floor(totalPieces / CRATE_SIZE);
                const pieces = totalPieces % CRATE_SIZE;
                return { crates, pieces, total: totalPieces };
            }

            // Initialize Chart.js
            const ctx = document.getElementById('dailyChart').getContext('2d');
            window.dailyChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: @json($chartData['labels']),
                    datasets: [{
                        label: 'Daily Egg Production (Pieces)',
                        data: @json($chartData['daily_egg_production']),
                        backgroundColor: '#0d6efd',
                        borderColor: '#0d6efd',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: { y: { beginAtZero: true } }
                }
            });
        </script>
        {{-- <script src="{{ asset('js/daily-list.init.js') }}"></script> --}}
    </div>
</div>
@endsection