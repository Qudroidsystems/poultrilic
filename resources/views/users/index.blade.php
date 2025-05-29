@extends('layouts.master')

@section('content')
<?php
use Spatie\Permission\Models\Role;
?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Users</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">User Management</a></li>
                                <li class="breadcrumb-item active">Users</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End page title -->

            <!-- Users by Role Chart -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Users by Role</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="usersByRoleChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
            </div>

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

            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div id="userList">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-xxl-3">
                                        <div class="search-box">
                                            <input type="text" class="form-control search" placeholder="Search users">
                                            <i class="ri-search-line search-icon"></i>
                                        </div>
                                    </div>
                                    <div class="col-xxl-3 col-sm-6">
                                        {{-- <div>
                                            <select class="form-control" data-choices data-choices-search-false data-choices-removeItem multiple data-choices-limit="Required Limit" data-choices-text-unique-true>
                                                @foreach ($roles as $role => $name)
                                                    <option value="{{ $role }}">{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </div> --}}
                                    </div>
                                    <div class="col-xxl-3 col-sm-6">
                                        <div>
                                            <select class="form-control" id="idRole" data-choices data-choices-search-false data-choices-removeItem>
                                                <option value="all">Select Role</option>
                                                @foreach ($roles as $role => $name)
                                                    <option value="{{ $role }}">{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xxl-2 col-sm-6">
                                        <div>
                                            <select class="form-control" id="idEmail" data-choices data-choices-search-false data-choices-removeItem>
                                                <option value="all">Select Email</option>
                                                @foreach ($data as $user)
                                                    <option value="{{ $user->email }}">{{ $user->email }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xxl-1 col-sm-6">
                                        <button type="button" class="btn btn-secondary w-100" onclick="filterData();"><i class="bi bi-funnel align-baseline me-1"></i> Filters</button>
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
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-0">Users <span class="badge bg-dark-subtle text-dark ms-1">{{ $data->total() }}</span></h5>
                                </div>
                                <div class="flex-shrink-0">
                                   
                                        <div class="d-flex flex-wrap align-items-start gap-2">
                                                <button class="btn btn-subtle-danger d-none" id="remove-actions" onclick="deleteMultiple()"><i class="ri-delete-bin-2-line"></i></button>
                                            @can('Create user')
                                                <button type="button" class="btn btn-primary add-btn" data-bs-toggle="modal" data-bs-target="#showModal"><i class="bi bi-plus-circle align-baseline me-1"></i> Add User</button>
                                            @endcan
                                        </div>
                                  
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-centered align-middle table-nowrap mb-0" id="userList">
                                        <thead class="table-active">
                                            <tr>
                                                <th><div class="form-check"><input class="form-check-input" type="checkbox" value="option" id="checkAll"><label class="form-check-label" for="checkAll"></label></div></th>
                                                <th class="sort cursor-pointer" data-sort="name">Name</th>
                                                <th class="sort cursor-pointer" data-sort="email">Email</th>
                                                <th class="sort cursor-pointer" data-sort="role">Role</th>
                                                <th class="sort cursor-pointer" data-sort="datereg">Date Registered</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="list form-check-all">
                                            @forelse ($data as $key => $user)
                                                <tr>
                                                    <td class="id" data-id="{{ $user->id }}">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="chk_child">
                                                            <label class="form-check-label"></label>
                                                        </div>
                                                    </td>
                                                    {{-- <td class="id" style="display:none;"><a href="javascript:void(0);" class="fw-medium link-primary">{{ $user->id }}</a></td> --}}
                                                    <td class="name" data-name="{{ $user->name }}">
                                                        <div class="d-flex align-items-center">
                                                            <div>
                                                                <h6 class="mb-0"><a href="{{ route('users.show', $user->id) }}" class="text-reset products">{{ $user->name }}</a></h6>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="email" data-email="{{ $user->email }}">{{ $user->email }}</td>
                                                    <td class="role" data-roles="{{ $user->getRoleNames()->implode(',') }}">
                                                        <div>
                                                            @if(!empty($user->getRoleNames()))
                                                                @foreach($user->getRoleNames() as $val)
                                                                    <label class="badge bg-primary">{{ $val }}</label>
                                                                @endforeach
                                                            @else
                                                                <label class="badge bg-secondary">No roles</label>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td class="datereg">{{ $user->created_at->format('Y-m-d') }}</td>
                                                    <td>
                                                        <ul class="d-flex gap-2 list-unstyled mb-0">
                                                            @can('View user')
                                                                 <li>
                                                                    <a href="{{ route('users.show', $user->id) }}" class="btn btn-subtle-primary btn-icon btn-sm"><i class="ph-eye"></i></a>
                                                                 </li>
                                                            @endcan
                                                           @can('Update user')
                                                                <li>
                                                                    <a href="javascript:void(0);" class="btn btn-subtle-secondary btn-icon btn-sm edit-item-btn"><i class="ph-pencil"></i></a>
                                                                </li>
                                                           @endcan
                                                           @can('Delete user')
                                                                <li>
                                                                   <a href="javascript:void(0);" class="btn btn-subtle-danger btn-icon btn-sm remove-item-btn"><i class="ph-trash"></i></a>
                                                                </li>
                                                           @endcan
                                                           
                                                        </ul>
                                                    </td>
                                                </tr>
                                                @empty
                                                      <tr>
                                                        <td colspan="7" class="noresult" style="display: block;">No results found</td>
                                                    </tr>
                                            
                                            @endforelse
                                          
                                        </tbody>
                                    </table>
                                </div>
                                <div class="row mt-3 align-items-center" id="pagination-element">
                                    <div class="col-sm">
                                        <div class="text-muted text-center text-sm-start">
                                            Showing <span class="fw-semibold">{{ $data->count() }}</span> of <span class="fw-semibold">{{ $data->total() }}</span> Results
                                        </div>
                                    </div>
                                    <div class="col-sm-auto mt-3 mt-sm-0">
                                        <div class="pagination-wrap hstack gap-2 justify-content-center">
                                            <a class="page-item pagination-prev {{ $data->onFirstPage() ? 'disabled' : '' }}" href="{{ $data->previousPageUrl() }}">
                                                <i class="mdi mdi-chevron-left align-middle"></i>
                                            </a>
                                            <ul class="pagination listjs-pagination mb-0">
                                                @foreach ($data->links()->elements[0] as $page => $url)
                                                    <li class="page-item {{ $data->currentPage() == $page ? 'active' : '' }}">
                                                        <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                            <a class="page-item pagination-next {{ $data->hasMorePages() ? '' : 'disabled' }}" href="{{ $data->nextPageUrl() }}">
                                                <i class="mdi mdi-chevron-right align-middle"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                               
                               
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add User Modal -->
        <div id="showModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 id="exampleModalLabel" class="modal-title">Add User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form class="tablelist-form" autocomplete="off" id="add-user-form">
                        <div class="modal-body">
                            <input type="hidden" id="add-id-field" name="id">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" id="name" name="name" class="form-control" placeholder="Enter name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" name="email" class="form-control" placeholder="Enter email" required>
                            </div>
                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select id="role" name="roles[]" class="form-control" multiple required>
                                    @foreach (Spatie\Permission\Models\Role::all() as $role)
                                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
                            </div>
                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label">Confirm Password</label>
                                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="Confirm password" required>
                            </div>
                            <div class="alert alert-danger d-none" id="alert-error-msg"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary" id="add-btn">Add User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div id="editModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 id="exampleModalLabel" class="modal-title">Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form class="tablelist-form" autocomplete="off" id="edit-user-form">
                        <div class="modal-body">
                            <input type="hidden" id="edit-id-field" name="id">
                            <div class="mb-3">
                                <label for="edit-name" class="form-label">Name</label>
                                <input type="text" id="edit-name" name="name" class="form-control" placeholder="Enter name" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit-email" class="form-label">Email</label>
                                <input type="email" id="edit-email" name="email" class="form-control" placeholder="Enter email" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit-role" class="form-label">Role</label>
                                <select id="edit-role" name="roles[]" class="form-control" multiple required>
                                    @foreach (Spatie\Permission\Models\Role::all() as $role)
                                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="edit-password" class="form-label">Password (optional)</label>
                                <input type="password" id="edit-password" name="password" class="form-control" placeholder="Enter new password">
                            </div>
                            <div class="mb-3">
                                <label for="edit-password_confirmation" class="form-label">Confirm Password</label>
                                <input type="password" id="edit-password_confirmation" name="password_confirmation" class="form-control" placeholder="Confirm new password">
                            </div>
                            <div class="alert alert-danger d-none" id="alert-error-msg"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary" id="update-btn">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete User Modal -->
        <div id="deleteRecordModal" class="modal fade zoomIn" tabindex="-1" aria-hidden="true">
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
                                <p class="text-muted fs-lg mx-3 mb-0">Are you sure you want to remove this record?</p>
                            </div>
                        </div>
                        <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                            <button type="button" class="btn w-sm btn-light btn-hover" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn w-sm btn-danger btn-hover" id="delete-record">Yes, Delete It!</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Page-content -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Scripts -->
    {{-- <script src="{{ asset('theme/layouts/assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('theme/layouts/assets/js/list.min.js') }}"></script>
    <script src="{{ asset('theme/layouts/assets/js/choices.min.js') }}" defer></script>
    <script src="{{ asset('theme/layouts/assets/js/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('js/user-list.init.js') }}"></script> --}}
    <!-- Chart Initialization -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        var ctx = document.getElementById("usersByRoleChart").getContext("2d");
        new Chart(ctx, {
            type: "bar",
            data: {
                labels: @json(array_keys($role_counts)),
                datasets: [{
                    label: "Users by Role",
                    data: @json(array_values($role_counts)),
                    backgroundColor: ["#4e73df", "#1cc88a", "#36b9cc", "#f6c23e", "#e74a3b"],
                    borderColor: ["#4e73df", "#1cc88a", "#36b9cc", "#f6c23e", "#e74a3b"],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: "Number of Users"
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: "Roles"
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: "top"
                    }
                }
            }
        });
    });
    </script>
</div>
@endsection