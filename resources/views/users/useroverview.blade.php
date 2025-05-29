@extends('layouts.master')
@section('content')
    <!-- ============================================================== -->
    <!-- Start right Content here -->
    <!-- ============================================================== -->
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                <!-- start page title -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0">User Management</h4>
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="javascript: void(0);">User</a></li>
                                    <li class="breadcrumb-item active">User Overview</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end page title -->
                <div class="row">
                    <div class="col-xxl-12">
                        <div class="d-flex align-items-center flex-wrap gap-2 mb-4">
                            <ul class="nav nav-pills arrow-navtabs nav-secondary gap-2 flex-grow-1 order-2 order-lg-1" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link active" data-bs-toggle="tab" href="#personalDetails" role="tab" aria-selected="true">
                                        User Details
                                    </a>
                                </li>
                            </ul>
                            <div class="flex-shrink-0 ms-auto order-1 order-lg-2">
                                <a href="{{ route('users.index') }}" class="btn btn-secondary"><i class="bi bi-pencil-square align-baseline me-1"></i> << Back</a>
                            </div>
                        </div>
                        <div class="card">
                            <div class="tab-content">
                                <div class="tab-pane active" id="personalDetails" role="tabpanel">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">User Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <form action="javascript:void(0);">
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="mb-3">
                                                        <label for="firstnameInput" class="form-label">First Name</label>
                                                        <input type="text" class="form-control" name="fname" id="firstnameInput" placeholder="Enter your firstname" value="{{ $userbio->firstname ?? 'No info' }}">
                                                    </div>
                                                </div>
                                                <!--end col-->
                                                <div class="col-lg-6">
                                                    <div class="mb-3">
                                                        <label for="lastnameInput" class="form-label">Last Name</label>
                                                        <input type="text" class="form-control" name="lname" id="lastnameInput" placeholder="Enter your last name" value="{{ $userbio->lastname ?? 'No info' }}">
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="mb-3">
                                                        <label for="othernameInput" class="form-label">Other Name</label>
                                                        <input type="text" class="form-control" name="oname" id="othernameInput" placeholder="Enter your other name" value="{{ $userbio->othernames ?? 'No info' }}">
                                                    </div>
                                                </div>
                                                <!--end col-->
                                                <div class="col-lg-6">
                                                    <div class="mb-3">
                                                        <label for="phonenumberInput" class="form-label">Phone Number</label>
                                                        <input type="text" class="form-control" name="phone" id="phonenumberInput" placeholder="Enter your phone number" value="{{ $userbio->phone ?? 'No info' }}">
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="mb-3">
                                                        <label for="emailInput" class="form-label">Email Address</label>
                                                        <input type="email" class="form-control" name="email" id="emailInput" placeholder="Enter your email" value="{{ $userbio->email ?? 'No info' }}">
                                                    </div>
                                                </div>
                                                <!--end col-->
                                                <div class="col-lg-6">
                                                    <div class="mb-3">
                                                        <label for="addressInput" class="form-label">Address</label>
                                                        <input type="text" class="form-control" name="address" id="addressInput" placeholder="Enter your address" value="{{ $userbio->address ?? 'No info' }}">
                                                    </div>
                                                </div>
                                                <!--end col-->
                                                <div class="col-lg-6">
                                                    <div class="mb-3">
                                                        <label for="birthDateInput" class="form-label">Birth of Date</label>
                                                        <input type="text" class="form-control" name="dob" data-provider="flatpickr" id="birthDateInput" data-date-format="d M, Y" placeholder="Select date" value="{{ $userbio->dob ?? 'No info' }}">
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="mb-3">
                                                        <label for="genderInput" class="form-label">Gender</label>
                                                        <div>
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="radio" name="gender" id="genderMale" value="Male" {{ $userbio && $userbio->gender === 'Male' ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="genderMale">Male</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="radio" name="gender" id="genderFemale" value="Female" {{ $userbio && $userbio->gender === 'Female' ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="genderFemale">Female</label>
                                                            </div>
                                                            @if(!$userbio || !$userbio->gender)
                                                                <span class="text-muted">No info</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="mb-3">
                                                        <label for="maritalstatusInput" class="form-label">Marital Status</label>
                                                        <div>
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="radio" name="maritalstatus" id="maritalSingle" value="Single" {{ $userbio && $userbio->maritalstatus === 'Single' ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="maritalSingle">Single</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="radio" name="maritalstatus" id="maritalMarried" value="Married" {{ $userbio && $userbio->maritalstatus === 'Married' ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="maritalMarried">Married</label>
                                                            </div>
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="radio" name="maritalstatus" id="maritalDivorced" value="Divorced" {{ $userbio && $userbio->maritalstatus === 'Divorced' ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="maritalDivorced">Divorced</label>
                                                            </div>
                                                            @if(!$userbio || !$userbio->maritalstatus)
                                                                <span class="text-muted">No info</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="mb-3">
                                                        <label for="nationalityInput" class="form-label">Nationality</label>
                                                        <input type="text" class="form-control" name="nationality" id="nationalityInput" placeholder="Enter your nationality" value="{{ $userbio->nationality ?? 'No info' }}">
                                                    </div>
                                                </div>
                                                <!--end col-->
                                                <div class="col-lg-12">
                                                    <div class="hstack gap-2 justify-content-end">
                                                        <button type="submit" class="btn btn-primary">Updates</button>
                                                        <button type="button" class="btn btn-subtle-danger">Cancel</button>
                                                    </div>
                                                </div>
                                                <!--end col-->
                                            </div>
                                            <!--end row-->
                                        </form>
                                    </div>
                                </div>
                                <!--end tab-pane-->
                            </div>
                        </div>
                    </div>
                    <!--end col-->
                </div>
                <!--end row-->
            </div>
            <!-- container-fluid -->
        </div><!-- End Page-content -->
    @endsection