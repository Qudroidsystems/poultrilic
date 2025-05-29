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
                                <h4 class="mb-sm-0">Poultry Analytics</h4>

                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboards</a></li>
                                        <li class="breadcrumb-item active">Poultry Analytics</li>
                                    </ol>
                                </div>

                            </div>
                        </div>
                    </div>
                    <!-- end page title -->

                    <div class="row">
                        <div class="col-xxl-3 col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div class="flex-grow-1">
                                            <div class="d-flex flex-column h-100">
                                                <p class="fs-md text-muted mb-4">Total Birds</p>
                                                <h3 class="mb-0 mt-auto"><span class="counter-value" data-target="3652">0</span> <small class="text-success fs-xs mb-0 ms-1"><i class="bi bi-arrow-up me-1"></i> 06.19%</small></h3>
                                            </div>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <div id="property_sale" data-colors='["--tb-primary"]' dir="ltr"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div><!--end col-->
                        <div class="col-xxl-3 col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div class="flex-grow-1">
                                            <div class="d-flex flex-column h-100">
                                                <p class="fs-md text-muted mb-4">Current birds</p>
                                                <h3 class="mb-0 mt-auto"><span class="counter-value" data-target="1524">0</span> <small class="text-success fs-xs mb-0 ms-1"><i class="bi bi-arrow-up me-1"></i> 02.33%</small></h3>
                                            </div>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <div id="property_rent" data-colors='["--tb-warning"]' dir="ltr"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div><!--end col-->
                        <div class="col-xxl-3 col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div class="flex-grow-1">
                                            <div class="d-flex flex-column h-100">
                                                <p class="fs-md text-muted mb-4">Total Egg Prod.</p>
                                                <h3 class="mb-0 mt-auto"><span class="counter-value" data-target="149.36">0</span>k <small class="text-success fs-xs mb-0 ms-1"><i class="bi bi-arrow-up me-1"></i> 12.33%</small></h3>
                                            </div>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <div id="visitors_chart" data-colors='["--tb-secondary"]' dir="ltr"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div><!--end col-->
                        <div class="col-xxl-3 col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <div class="flex-grow-1">
                                            <div class="d-flex flex-column h-100">
                                                <p class="fs-md text-muted mb-4">Mortality</p>
                                                <h3 class="mb-0 mt-auto"><span class="counter-value" data-target="2376">0</span> <small class="text-danger fs-xs mb-0 ms-1"><i class="bi bi-arrow-down me-1"></i> 09.57%</small></h3>
                                            </div>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <div id="residency_property" data-colors='["--tb-success"]' dir="ltr"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div><!--end col-->
                    </div><!--end row-->

                    <div class="row">
                       
                        <div class="col-xxl-12">
                            <div class="card">
                                <div class="card-header d-flex align-items-center">
                                    <h5 class="card-title flex-grow-1 mb-0">Revenue Overview</h5>
                                    <div class="flex-shrink-0">
                                        <input type="text" class="form-control form-control-sm" id="exampleInputPassword1" data-provider="flatpickr" data-range-date="true" data-date-format="d M, Y" data-default-date="01 Feb 2023 to 28 Feb 2023">
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-lg-3">
                                            <div class="nav flex-column nav-light nav-pills gap-3" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                                <a class="nav-link d-flex p-2 gap-3 active" id="revenue-tab" data-bs-toggle="pill" href="#revenue" role="tab" aria-controls="revenue" aria-selected="true">
                                                    <div class="avatar-sm flex-shrink-0">
                                                        <div class="avatar-title rounded bg-warning-subtle text-warning fs-2xl">
                                                            <i class="bi bi-coin"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h5 class="text-reset">$<span class="counter-value" data-target="2478">0</span>M</h5>
                                                        <p class="mb-0">Total Revenue</p>
                                                    </div>
                                                </a>
                                                <a class="nav-link d-flex p-2 gap-3" id="income-tab" data-bs-toggle="pill" href="#income" role="tab" aria-controls="income" aria-selected="false">
                                                    <div class="avatar-sm flex-shrink-0">
                                                        <div class="avatar-title rounded bg-success-subtle text-success fs-2xl">
                                                            <i class="bi bi-coin"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h5 class="text-reset">$<span class="counter-value" data-target="14587.37">0</span></h5>
                                                        <p class="mb-0">Total Income</p>
                                                    </div>
                                                </a>
                                                <a class="nav-link d-flex p-2 gap-3" id="property-sale-tab" data-bs-toggle="pill" href="#property-sale" role="tab" aria-controls="property-sale" aria-selected="false">
                                                    <div class="avatar-sm flex-shrink-0">
                                                        <div class="avatar-title rounded bg-danger-subtle text-danger fs-2xl">
                                                            <i class="bi bi-coin"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h5 class="text-reset"><span class="counter-value" data-target="2365">0</span></h5>
                                                        <p class="mb-0">Property Sell</p>
                                                    </div>
                                                </a>
                                                <a class="nav-link d-flex p-2 gap-3" id="_-tab" data-bs-toggle="pill" href="#propetry-rent" role="tab" aria-controls="propetry-rent" aria-selected="false">
                                                    <div class="avatar-sm flex-shrink-0">
                                                        <div class="avatar-title rounded bg-primary-subtle text-primary fs-2xl">
                                                            <i class="bi bi-coin"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h5 class="text-reset"><span class="counter-value" data-target="3456">0</span></h5>
                                                        <p class="mb-0">Property Rent</p>
                                                    </div>
                                                </a>
                                            </div>
                                        </div><!--end col-->
                                        <div class="col-lg-9">
                                            <div class="tab-content text-muted">
                                                <div class="tab-pane active" id="revenue" role="tabpanel">
                                                    <div id="total_revenue" data-colors='["--tb-primary"]' class="apex-charts effect-chart" dir="ltr"></div>
                                                </div><!--end tab-->
                                                <div class="tab-pane" id="income" role="tabpanel">
                                                    <div id="total_income" data-colors='["--tb-success"]' class="apex-charts" dir="ltr"></div>
                                                </div>
                                                <div class="tab-pane" id="property-sale" role="tabpanel">
                                                    <div id="property_sale_chart" data-colors='["--tb-danger"]' class="apex-charts" dir="ltr"></div>
                                                </div>
                                                <div class="tab-pane" id="propetry-rent" role="tabpanel">
                                                    <div id="propetry_rent" data-colors='["--tb-info"]' class="apex-charts" dir="ltr"></div>
                                                </div>
                                            </div>
                                        </div><!--end col-->
                                    </div><!--end row-->
                                </div>
                            </div>
                        </div><!--end col-->
                    </div><!--end row-->

                    

                </div>
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->

          


@endsection

        

      