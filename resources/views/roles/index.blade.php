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
                            <li class="breadcrumb-item"><a href="javascript: void(0);">role Management</a></li>
                            <li class="breadcrumb-item active">roles</li>
                        </ol>
                    </div>

                </div>
            </div>
        </div>
        <!-- end page title -->
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
        
        @if (\Session::has('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ \Session::get('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif
        @if (\Session::has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ \Session::get('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center g-2">
                    <div class="col-lg-3 me-auto">
                        {{-- <h6 class="card-title mb-0">role<span class="badge bg-primary ms-1 align-baseline">1452</span></h6> --}}
                    </div><!--end col-->
                   
                    <div class="col-lg-auto">
                        <div class="hstack gap-2">
                            <a href="#" data-bs-toggle="modal"  data-bs-target="#addRoleModalgrid" class="btn btn-secondary"><i class="bi bi-plus-circle align-baseline me-1"></i> Add Role</a>
                           
                        </div>
                    </div><!--end col-->
                </div>
            </div>
        </div><!--end card-->

        <div class="row row-cols-xxl-5">
            @foreach ($roles as $role )
                <div class="col">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                
                                <div class="flex-shrink-0">
                                    <div class="dropdown"> 
                                        <button class="btn btn-light btn-icon btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots-vertical"></i> 
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu">
                                            @can('Update user-role')
                                                 <li><a class="dropdown-item edit-list" href="{{ route('roles.adduser',$role->id) }}" ><i class="bi bi-pencil-square me-1 align-baseline"></i>Add user</a></li>
                                            @endcan
                    
                                            @can('Remove user-role')
                                                    <li>
                                                        <form method="POST" action="{{ route('roles.destroy', $role->id) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="dropdown-item edit-list" type="submit" ><i class="bi bi-trash3 me-1 align-baseline"></i>Delete Role</button>
                                                        </form>
                                                </li>  
                                            @endcan
                                           
                                           
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center mt-4 mb-2">
                            
                                <a href="apps-instructors-overview.html"><h6 class="fs-md mt-4 mb-1">{{ $role->name }}</h6></a>
                              
                                <h2 class="{{ $role->badge }}">Role Badge</h2>
                            </div>
                            <ul class="list-unstyled text-muted vstack gap-2 mb-0">
                                <?php
                                // $roles = Role::orderBy('name','DESC')->get();
                                // foreach ($roles as $role => $value) {
                                    $roles_num =  DB::table('model_has_roles')->where('role_id',$role->id)->count();
                                    $role_permissions = $role->permissions->pluck('name')->take(3);
                                // }
                                ?>
                                <li>
                                    @foreach ($role_permissions as $role_permission)
                                        <li>
                                            <a href="#" class="text-reset"><i class="bi bi-telephone align-baseline me-1"></i> {{$role_permission}}</a>
                                        </li>
                                    @endforeach
                                </li>
                                @can('View role')<a href="{{ route('roles.show',$role->id) }}"> <em>...and more</a>@endcan</em>
                            </ul>
                        </div>
                        <div class="card-body border-top border-dashed d-flex align-items-center">
                            <div class="flex-grow-1">
                                <span class="badge bg-warning-subtle text-primary"><i class="bi bi-star-fill align-baseline me-1"></i>Total users: {{ $roles_num }}</span>
                            </div>
                            <div class="flex-shrink-0"></div>
                            @can('View role')
                                <a href="{{ route('roles.show',$role->id) }}" class="link-effect">View roles <i class="bi bi-arrow-right align-baseline ms-1"></i></a>
                            @endcan
                           
                        </div>
                    </div><!--end card-->
                </div><!--end col-->
            @endforeach
          
        </div><!--end row-->


                <!-- Grids in modals -->
                <div class="modal fade" id="addRoleModalgrid" tabindex="-1" aria-labelledby="exampleModalgridLabel" aria-modal="true">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalgridLabel">Add role</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="kt_modal_add_role_form" class="form" action="{{ route('roles.store') }}" method="POST">
                                    @csrf
                                    <div class="row g-3">
                                        <div class="col-xxl-6">
                                            <div>
                                                <label for="firstName" class="form-label">Role Name</label>
                                                <input type="text" class="form-control" placeholder="Enter a role name" name="name">
                                            </div>
                                        </div><!--end col-->
                                        <div class="col-xxl-6">
                                            <div> 
                                                <label for="firstName" class="form-label">Role Badge</label>
                                                <!--begin::Input-->
                                                <select name="badge" class="form-control" data-kt-select2="true" data-placeholder="Select option" data-allow-clear="true" data-kt-user-table-filter="two-step" data-hide-search="true">
                                                    <option></option>
                                                    <option value="badge bg-light">Light grey</option>
                                                    <option value="badge bg-dark"> Dark</option>
                                                    <option value="badge bg-primary">Blue</option>
                                                    <option value="badge bg-secondary">Light blue</option>
                                                    <option value="badge bg-success">Light green</option>
                                                    <option value="badge bg-info">Purple</option>
                                                    <option value="badge bg-warning">Yellow</option>
                                                    <option value="badge bg-danger">Red</option>
                                            </select>
                                                <!--end::Input-->
                                            </div>
                                        </div><!--end col-->
                                        <div class="col-lg-12">
                                        
                                    
                                        <!--begin::Table wrapper-->
                                        <div class="table-responsive">
                                            <!--begin::Table-->
                                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                                <!--begin::Table body-->
                                                <tbody class="text-gray-600 fw-semibold">
                                                    <!--begin::Table row-->
                                                    <tr>
                                                        <td class="text-gray-800">
                                                            Administrator Access

                                                            <span class="ms-2" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-content="Allows a full access to the system">
                                                                <i class="ki-duotone ki-information fs-7"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>                                                </span>
                                                        </td>
                                                        <td>
                                                            <!--begin::Checkbox-->
                                                            <label class="form-check form-check-custom form-check-solid me-9">
                                                                <input class="form-check-input" type="checkbox" value="" id="kt_roles_select_all" />
                                                                <span class="form-check-label" for="kt_roles_select_all">
                                                                    Select all
                                                                </span>
                                                            </label>
                                                            <!--end::Checkbox-->
                                                        </td>
                                                    </tr>
                                                    <!--end::Table row-->


                                                        @foreach (array_unique($perm_title) as $value)
                                                                    <?php
                                                                    $permission = Permission::where('title',$value)->get();
                                                                    $word="";

                                                                    ?>

                                                            <tr>
                                                                    <!--begin::Label-->
                                                                    <td class="text-gray-800">{{ $value }}</td>
                                                                    <!--end::Label-->


                                                                    @foreach ($permission as $key => $v)
                                                                                    <?php
                                                                                        $word = ''; // default fallback
                                                                                
                                                                                        if (strpos($v->name, "View ") !== false) {
                                                                                            $word = "View";
                                                                                        } elseif (strpos($v->name, "Create ") !== false) {
                                                                                            $word = "Create";
                                                                                        } elseif (strpos($v->name, "Update ") !== false) {
                                                                                            $word = "Edit";
                                                                                        } elseif (strpos($v->name, "Delete ") !== false) {
                                                                                            $word = "Delete";
                                                                                        } elseif (strpos($v->name, "Update user-role") !== false) {
                                                                                            $word = "Update user role";
                                                                                        } elseif (strpos($v->name, "Add user-role") !== false) {
                                                                                                $word = "Add user role";
                                                                                        }
                                                                                        elseif (strpos($v->name, "Remove user-role") !== false) {
                                                                                                $word = "Remove user role";
                                                                                        }
                                                                                        elseif (strpos($v->name, "Show ") !== false) {
                                                                                                $word = "Show";
                                                                                        }
                                
                                                                                    ?>
                                                                                        <!--begin::Options-->
                                                                                        <td>
                                                                                            <!--begin::Wrapper-->
                                                                                            <div class="d-flex">
                                                                                                <!-- Custom Outline Checkboxes -->
                                                                                                <div class="form-check form-check-outline form-check-primary mb-3">
                                                                                                    <input class="form-check-input" type="checkbox" value="{{ $v->id }}" name="permission[]">
                                                                                                    <label class="form-check-label" for="formCheck13">
                                                                                                        {{ $word }}
                                                                                                    </label>
                                                                                                </div>
                                                                                            </div>
                                                                                            <!--end::Wrapper-->
                                                                                        </td>
                                                                                        <!--end::Options-->
                                                                    @endforeach
                                                                
                                                            </tr>


                                                        @endforeach



                                                                                <!--begin::Table row-->

                                                        </tbody>
                                                <!--end::Table body-->
                                            </table>
                                            <!--end::Table-->
                                        </div>
                                        <!--end::Table wrapper-->
                                        
                                        </div><!--end col-->
                                    
                                        <div class="col-lg-12">
                                            <div class="hstack gap-2 justify-content-end">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Submit</button>
                                            </div>
                                        </div><!--end col-->
                                    </div><!--end row-->
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
    </div>
    <!-- End Page-content -->

   <script>

    document.addEventListener("DOMContentLoaded", function () {
        const selectAllCheckbox = document.getElementById("kt_roles_select_all");
        const permissionCheckboxes = document.querySelectorAll('input[name="permission[]"]');

        if (!selectAllCheckbox) {
            console.warn("Select all checkbox not found.");
            return;
        }

        if (permissionCheckboxes.length === 0) {
            console.warn("No permission checkboxes found.");
            selectAllCheckbox.disabled = true;
            return;
        }

        selectAllCheckbox.addEventListener("change", function () {
            console.log(`Select all checkbox toggled, state: ${this.checked}`);
            permissionCheckboxes.forEach((checkbox) => {
                checkbox.checked = this.checked;
            });
            console.log(`Updated ${permissionCheckboxes.length} permission checkboxes to ${this.checked ? "checked" : "unchecked"}`);
        });

        function updateSelectAllState() {
            const allChecked = Array.from(permissionCheckboxes).every(checkbox => checkbox.checked);
            const someChecked = Array.from(permissionCheckboxes).some(checkbox => checkbox.checked);
            selectAllCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate = someChecked && !allChecked;
        }

        permissionCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener("change", function () {
                console.log(`Permission checkbox toggled, value: ${this.value}, state: ${this.checked}`);
                updateSelectAllState();
            });
        });

        // Initial state update
        updateSelectAllState();

        // Optional: Validate form submission
        document.getElementById("kt_modal_add_role_form").addEventListener("submit", function (e) {
            if (!Array.from(permissionCheckboxes).some(checkbox => checkbox.checked)) {
                e.preventDefault();
                alert("Please select at least one permission.");
            }
        });
    });
   </script>

</div>
@endsection