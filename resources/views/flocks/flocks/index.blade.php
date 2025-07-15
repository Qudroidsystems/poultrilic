@extends('layouts.master')
@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Flocks</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Flock Management</a></li>
                                <li class="breadcrumb-item active">Flocks</li>
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

            <a href="{{ route('dashboard') }}" class="btn btn-info mb-3"><i class="bi bi-bar-chart me-1"></i> View Dashboard</a>

            <div id="flockList">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3 align-items-center">
                                    <div class="col-md-4">
                                        <div class="search-box">
                                            <input type="text" class="form-control search" id="searchFlock" placeholder="Search flocks by ID or bird count" aria-label="Search flocks">
                                            <i class="ri-search-line search-icon"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <select id="birdCountFilter" class="form-control" data-choices data-choices-search-true data-choices-removeItem>
                                            <option value="all">Select Initial Bird Count Range</option>
                                            <option value="0-100">0-100</option>
                                            <option value="101-200">101-200</option>
                                            <option value="201-500">201-500</option>
                                            <option value="501+">501+</option>
                                        </select>
                                    </div>
                                    <div class="col-md-auto">
                                        <button type="button" class="btn btn-secondary w-100" onclick="filterData();"><i class="bi bi-funnel align-baseline me-1"></i> Filter</button>
                                    </div>
                                    <div class="col-md-auto ms-auto">
                                        <div class="hstack gap-2">
                                            @can('Create flock')
                                                <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#addFlockModal"><i class="bi bi-plus-circle align-baseline me-1"></i> Add Flock</button>
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
                                <h5 class="card-title mb-0 flex-grow-1">Flocks <span class="badge bg-dark-subtle text-dark ms-1">{{ $flocks->total() }}</span></h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-centered align-middle table-nowrap mb-0" id="flockTable">
                                        <thead class="table-active">
                                            <tr>
                                                <th scope="col">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="checkAll" value="option">
                                                        <label class="form-check-label"></label>
                                                    </div>
                                                </th>
                                                <th scope="col">Initial Bird Count</th>
                                                <th scope="col">Current Bird Count</th>
                                                <th scope="col">Created At</th>
                                                <th scope="col">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="list form-check-all">
                                            @forelse ($flocks as $flock)
                                                <tr class="list" key="{{ $flock->id }}">
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input chk-child id" type="checkbox" name="chk_child" value="{{ $flock->id }}" data-id="{{ $flock->id }}">
                                                            <label class="form-check-label"></label>
                                                        </div>
                                                    </td>
                                                    <td class="initial_bird_count">{{ $flock->initial_bird_count }}</td>
                                                    <td class="current_bird_count">{{ $flock->current_bird_count }}</td>
                                                    <td class="created_at">{{ $flock->created_at->format('Y-m-d') }}</td>
                                                    <td>
                                                        <div class="hstack gap-2">
                                                            @can('View flock')
                                                                <a href="{{ route('week-entries.index', $flock->id) }}" class="btn btn-subtle-primary btn-icon btn-sm" title="View week entries"><i class="ph-eye"></i></a>
                                                            @endcan
                                                            @can('Update flock')
                                                                <button type="button" class="btn btn-subtle-secondary btn-icon btn-sm edit-item-btn" title="Edit flock"><i class="ph-pencil"></i></button>
                                                            @endcan
                                                            @can('Delete flock')
                                                                <button type="button" class="btn btn-subtle-danger btn-icon btn-sm remove-item-btn" title="Delete flock"><i class="ph-trash"></i></button>
                                                            @endcan
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr class="noresult">
                                                    <td colspan="5" class="text-center">No flocks found</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                    <template id="flockRowTemplate">
                                        <tr class="list">
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input chk-child id" type="checkbox" name="chk_child" value="{id}" data-id="{id}">
                                                    <label class="form-check-label"></label>
                                                </div>
                                            </td>
                                            <td class="initial_bird_count">{initial_bird_count}</td>
                                            <td class="current_bird_count">{current_bird_count}</td>
                                            <td class="created_at">{created_at}</td>
                                            <td>
                                                <div class="hstack gap-2">
                                                    @can('View flock')
                                                        <a href="/flocks/{id}/week-entries" class="btn btn-subtle-primary btn-icon btn-sm" title="View week entries"><i class="ph-eye"></i></a>
                                                    @endcan
                                                    @can('Update flock')
                                                        <button type="button" class="btn btn-subtle-secondary btn-icon btn-sm edit-item-btn" title="Edit flock"><i class="ph-pencil"></i></button>
                                                    @endcan
                                                    @can('Delete flock')
                                                        <button type="button" class="btn btn-subtle-danger btn-icon btn-sm remove-item-btn" title="Delete flock"><i class="ph-trash"></i></button>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </div>
                                <div class="row mt-3 align-items-center" id="pagination-element">
                                    <div class="col-sm">
                                        <div class="text-muted text-center text-sm-start">
                                            Showing <span class="fw-semibold">{{ $flocks->count() }}</span> of <span class="fw-semibold">{{ $flocks->total() }}</span> Results
                                        </div>
                                    </div>
                                    <div class="col-sm-auto mt-3 mt-sm-0">
                                        <div class="pagination-wrap hstack gap-2 justify-content-center">
                                            {{ $flocks->links() }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Flock Distribution Chart</h5>
                                <canvas id="flockChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Week Entries Chart</h5>
                                <canvas id="weekChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-body" id="flock-stats">
                                <p class="text-muted">Select a flock to view statistics.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-body" id="all-flocks-stats">
                                @if (isset($allFlocksStats))
                                    <h5 class="card-title">All Flocks Statistics</h5>
                                    <p>Total Flocks: {{ $allFlocksStats['total_flocks'] }}</p>
                                    <p>Total Initial Bird Count: {{ $allFlocksStats['total_initial_bird_count'] }}</p>
                                    <p>Total Current Bird Count: {{ $allFlocksStats['total_current_bird_count'] }}</p>
                                    <p>Average Initial Bird Count: {{ number_format($allFlocksStats['avg_initial_bird_count'], 2) }}</p>
                                    <p>Average Current Bird Count: {{ number_format($allFlocksStats['avg_current_bird_count'], 2) }}</p>
                                    <p>Total Weeks: {{ $allFlocksStats['total_weeks'] }}</p>
                                    <p>Total Daily Entries: {{ $allFlocksStats['total_daily_entries'] }}</p>
                                    <p>Total Egg Production: {{ $allFlocksStats['total_egg_production'] }} eggs</p>
                                    <p>Total Mortality: {{ $allFlocksStats['total_mortality'] }} birds</p>
                                    <p>Total Feeds Consumed: {{ $allFlocksStats['total_feeds_consumed'] }} kg</p>
                                @else
                                    <p class="text-muted">No statistics available.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Flock Modal -->
            <div id="addFlockModal" class="modal fade" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 id="addModalLabel" class="modal-title">Add Flock</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form class="tablelist-form" autocomplete="off" id="add-flock-form">
                            @csrf
                            <div class="modal-body">
                                <input type="hidden" id="add-id-field" name="id">
                                <div class="mb-3">
                                    <label for="initial_bird_count" class="form-label">Initial Bird Count</label>
                                    <input type="number" id="initial_bird_count" name="initial_bird_count" class="form-control" placeholder="Enter initial bird count" required min="0">
                                </div>
                                <div class="alert alert-danger d-none" id="add-error-msg"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" id="add-btn">Add Flock</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Edit Flock Modal -->
            <div id="editFlockModal" class="modal fade" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 id="editModalLabel" class="modal-title">Edit Flock</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form class="tablelist-form" autocomplete="off" id="edit-flock-form">
                            @csrf
                            @method('PUT')
                            <div class="modal-body">
                                <input type="hidden" id="edit-id-field" name="id">
                                <div class="mb-3">
                                    <label for="edit-initial_bird_count" class="form-label">Initial Bird Count</label>
                                    <input type="number" id="edit-initial_bird_count" name="initial_bird_count" class="form-control" placeholder="Enter initial bird count" required min="0">
                                </div>
                                <div class="mb-3">
                                    <label for="edit-current_bird_count" class="form-label">Current Bird Count</label>
                                    <input type="number" id="edit-current_bird_count" name="current_bird_count" class="form-control" placeholder="Enter current bird count" required min="0">
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

            <!-- Delete Flock Modal -->
            <div id="deleteRecordModal" class="modal fade zoomIn" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-md-5">
                            <div class="text-center">
                                <div class="text-danger">
                                    <i class="bi bi-trash display-4"></i>
                                </div>
                                <div class="mt-4">
                                    <h3 class="mb-2">Are you sure?</h3>
                                    <p class="text-muted fs-lg mx-3 mb-0">Are you sure you want to remove this flock?</p>
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
    </div>
</div>


    <!-- Custom JavaScript -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
@endsection