{{-- Left panel of the sign-in pages: csskabba's animated circles, with farm icons
     around the ring instead of staff photos (no user details are shown before sign-in). --}}
@php
    $farmName = \App\Support\FarmSettings::text('farm_name');
    $orbit = [
        ['mdi mdi-egg-outline', 'Eggs and laying rate'],
        ['ri-seedling-line', 'Feed and stock'],
        ['ri-line-chart-line', 'Analytics and reports'],
        ['ri-file-excel-2-line', 'Excel import'],
        ['ri-store-2-line', 'Sales and invoices'],
    ];
@endphp
<div class="col-xxl-5">
    <div class="card auth-card bg-secondary h-100 border-0 shadow-none d-none d-sm-block mb-0">
        <div class="card-body py-5 d-flex justify-content-between flex-column">
            <div class="text-center">
                <h3 class="text-white" style="animation: fadeInScale 0.6s ease;">{{ $title ?? 'Run your farm with confidence.' }}</h3>
                <p class="text-white opacity-75 fs-base">{{ $subtitle ?? 'Every bird, egg, bag of feed and naira, counted.' }}</p>
            </div>

            <div class="auth-effect-main my-5 position-relative rounded-circle d-flex align-items-center justify-content-center mx-auto">
                <div class="effect-circle-1 position-relative mx-auto rounded-circle d-flex align-items-center justify-content-center" style="animation: pulse 2s infinite;">
                    <div class="effect-circle-2 position-relative mx-auto rounded-circle d-flex align-items-center justify-content-center">
                        <div class="effect-circle-3 mx-auto rounded-circle position-relative text-white fs-4xl d-flex align-items-center justify-content-center" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(4px);">
                            <span class="text-primary ms-1" style="font-weight: 600;">{{ $farmName }}</span>
                        </div>
                    </div>
                </div>

                <ul class="auth-user-list list-unstyled">
                    @foreach ($orbit as $i => [$icon, $label])
                        <li style="animation-delay: {{ $i * 0.2 }}s;">
                            <span class="avatar-sm d-inline-block" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $label }}">
                                <span class="avatar-title bg-white shadow-lg overflow-hidden rounded-circle">
                                    <span class="pl-orbit-icon"><i class="{{ $icon }}"></i></span>
                                </span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="text-center">
                <p class="text-white opacity-75 mb-0 mt-3">
                    &copy; <script>document.write(new Date().getFullYear())</script> {{ $farmName }}. Created with <i class="mdi mdi-heart text-danger"></i> by Qudroid Systems
                </p>
            </div>
        </div>
    </div>
</div>
