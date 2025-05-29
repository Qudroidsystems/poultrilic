@extends('layouts.master')
@section('content')
<?php
use Spatie\Permission\Models\Role;
use App\Models\User;
use Spatie\Permission\Models\Permission;
?>

<div class="main-content">
   
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Role Management</h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Role Management</a></li>
                                <li class="breadcrumb-item active">Add user</li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center g-2">
                        <div class="col-lg-3 me-auto">
                            {{-- <h6 class="card-title mb-0">role<span class="badge bg-primary ms-1 align-baseline">1452</span></h6> --}}
                        </div><!--end col-->
                       
                        <div class="col-lg-auto">
                            <div class="hstack gap-2">
                                <a href="{{ route('roles.index') }}"    data-bs-target="#addRoleModalgrid" class="btn btn-secondary"><< Back</a>
                               
                            </div>
                        </div><!--end col-->
                    </div>
                </div>
            </div><!--end card-->

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0">Add User to {{ $role->name }} role</h4>
                        </div><!-- end card header -->
                        <div class="card-body">
                            <form id="kt_account_profile_details_form" class="form" action="{{ route('roles.updateuserrole') }}">
                                @csrf
                                    <div class="row gy-4">
                                        <div class="col-xxl-3 col-md-6">
                                            <div>
                                                <label  class="form-label">Role</label>
                                                <input type="hidden" name="roleid" class="form-control form-control-lg form-control-solid" readonly value="{{ $role->id }}" />
                                                <input type="text" name="role" class="form-control"  readonly value="{{ $role->name }}">
                                            </div>
                                        </div>
                                        <!--end col-->
                                        <div class="col-xxl-3 col-md-6">
                                            <div>
                                                <label for="labelInput" class="form-label">Select User</label>
                                                <select name="name" aria-label="Select a Staff" data-control="select2" data-placeholder="Select a Staff" class="form-select form-select-solid form-select-lg fw-semibold">
                                                    <option value="">Select a Staff...</option>
                                                    @foreach ($users as $user )
                                                       <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                   @endforeach
                                                </select>
                        
                                            </div>
                                        </div>
                                        
                                        {{-- <!--end col-->
                                        <div class="col-xxl-3 col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control" id="firstnamefloatingInput" placeholder="Enter your firstname">
                                                <label for="firstnamefloatingInput">Floating Input</label>
                                            </div>
                                        </div> --}}
                                        <!--end col-->
                                        <div class="col-xxl-3 col-md-6">
                                            <div class="form-floating">
                                                        <!-- Gradient Buttons -->
                                                    <button  type="submit" class="btn btn-primary bg-gradient">Add User</button>
                                            </div>
                                        </div>
                                        <!--end col-->

                                    </div>
                                    <!--end row-->
                            </form>
                            <!--end::Form-->
                        </div>
                      
                      
                    </div>
                </div>
                <!--end col-->
            </div>
            <!--end row-->

        </div> <!-- container-fluid -->
    </div><!-- End Page-content -->

</div>
@endsection