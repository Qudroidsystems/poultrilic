<!doctype html>
<html lang="en" data-layout="vertical" data-sidebar="dark" data-sidebar-size="lg" data-preloader="disable" data-theme="default" data-topbar="light" data-bs-theme="light">

<head>
    <meta charset="utf-8">
    @php $farmName = \App\Support\FarmSettings::text('farm_name'); @endphp
    <title>{{ $pagetitle ?? 'Dashboard' }} | {{ $farmName }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Poultry and farm management: flocks, livestock, sales, analytics and reports" name="description">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('theme/layouts/assets/images/favicon.ico') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link id="fontsLink" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.0.3/src/bold/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- Layout CSS — load BEFORE layout.js -->
    <link href="{{ asset('theme/layouts/assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('theme/layouts/assets/css/icons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('theme/layouts/assets/css/app.min.css') }}" rel="stylesheet">
    <link href="{{ asset('theme/layouts/assets/css/custom.min.css') }}" rel="stylesheet">
    <link href="{{ asset('theme/layouts/assets/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">

    <!-- layout.js must run AFTER the CSS above -->
    <script src="{{ asset('theme/layouts/assets/js/layout.js') }}"></script>
    {{-- Portal themes: appearance (light/dark/auto) + style (default/apple). Must stay in <head>. --}}
    <script src="{{ asset('js/portal-theme.js') }}?v={{ @filemtime(public_path('js/portal-theme.js')) }}"></script>

    <!-- NProgress -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.css"/>
    <script src="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.min.js"></script>

    <!-- Select2 + jQuery (used by some page scripts) -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    @include('layouts.inc.layout-styles')

    <!-- Route-specific CSS -->
    @if (Route::is('dashboard'))
        @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('users.*', 'user.*'))
        @include('layouts.pages-assets.css.users-list-css')
    @endif
    @if (Route::is('roles.*'))
        @include('layouts.pages-assets.css.roles-list-css')
    @endif
    @if (Route::is('permissions.*'))
        @include('layouts.pages-assets.css.permission-list-css')
    @endif
    @if (Route::is('week-entries.*'))
        @include('layouts.pages-assets.css.week-list-css')
    @endif
    @if (Route::is('daily-entries.*'))
        @include('layouts.pages-assets.css.daily-list-css')
    @endif
    @if (Route::is('flocks.*', 'expenses.*', 'settings.*', 'feed.*', 'sales.*', 'customers.*', 'poultry.*', 'imports.*', 'livestock.*', 'reports.*'))
        @include('layouts.pages-assets.css.finance-css')
    @endif
    @stack('styles')

    {{-- CB UI design system + themes, loaded LAST so page CSS can't override them.
         Remove class="cb-ui" from <body> to switch the global skin off. --}}
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link href="{{ asset('css/cb-ui.css') }}?v={{ @filemtime(public_path('css/cb-ui.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/portal-theme.css') }}?v={{ @filemtime(public_path('css/portal-theme.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/poultrilic.css') }}?v={{ @filemtime(public_path('css/poultrilic.css')) }}" rel="stylesheet">
</head>

<body class="cb-ui">
<div id="layout-wrapper">

    <!-- ========== SIDEBAR ========== -->
    <div class="app-menu navbar-menu">

        <!-- LOGO: the farm's own logo and name (Website › Settings / Business details) -->
        @php $brandLogo = \App\Services\Website\WebsiteContent::farm()->logo; @endphp
        <div class="navbar-brand-box">
            <a href="{{ route('poultry.overview') }}" class="logo logo-dark">
                <span class="logo-sm"><span class="pl-logo-mark @if ($brandLogo) has-img @endif">@if ($brandLogo)<img src="{{ $brandLogo }}" alt="">@else<i class="ri-seedling-fill"></i>@endif</span></span>
                <span class="logo-lg"><span class="pl-logo"><span class="pl-logo-mark @if ($brandLogo) has-img @endif">@if ($brandLogo)<img src="{{ $brandLogo }}" alt="">@else<i class="ri-seedling-fill"></i>@endif</span><span class="pl-logo-text">{{ $farmName }}</span></span></span>
            </a>
            <a href="{{ route('poultry.overview') }}" class="logo logo-light">
                <span class="logo-sm"><span class="pl-logo-mark @if ($brandLogo) has-img @endif">@if ($brandLogo)<img src="{{ $brandLogo }}" alt="">@else<i class="ri-seedling-fill"></i>@endif</span></span>
                <span class="logo-lg"><span class="pl-logo"><span class="pl-logo-mark @if ($brandLogo) has-img @endif">@if ($brandLogo)<img src="{{ $brandLogo }}" alt="">@else<i class="ri-seedling-fill"></i>@endif</span><span class="pl-logo-text">{{ $farmName }}</span></span></span>
            </a>
            <button type="button" class="btn btn-sm p-0 fs-3xl header-item float-end btn-vertical-sm-hover" id="vertical-hover">
                <i class="ri-record-circle-line"></i>
            </button>
        </div>

        <!-- NAV (scrollable) -->
        <div id="scrollbar">
            <div class="container-fluid">
                <div id="two-column-menu"></div>
                @include('layouts.inc.sidebar-menu')
            </div>
        </div><!-- /scrollbar -->

        <!-- ===== SIDEBAR LOGOUT FOOTER ===== -->
        @auth
        <div class="sidebar-footer">
            @php
                $sidebarUser = Auth::user();
                $sidebarPhoto = $sidebarUser->profile_image && \Illuminate\Support\Facades\Storage::disk('public')->exists($sidebarUser->profile_image)
                    ? asset('storage/' . $sidebarUser->profile_image) : null;
                $sidebarInitials = collect(explode(' ', trim($sidebarUser->name)))->filter()
                    ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('');
            @endphp
            <div class="sidebar-footer-user">
                @if ($sidebarPhoto)
                    <img src="{{ $sidebarPhoto }}" alt="{{ $sidebarUser->name }}">
                @else
                    <span class="sidebar-footer-avatar-initials">{{ $sidebarInitials }}</span>
                @endif
                <div class="sidebar-footer-user-info">
                    <div class="sidebar-footer-user-name">{{ $sidebarUser->name }}</div>
                    <div class="sidebar-footer-user-role">{{ $sidebarUser->roles->first()->name ?? 'User' }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" id="sidebar-logout-form">
                @csrf
                <button type="submit" class="sidebar-logout-btn">
                    <i class="mdi mdi-logout"></i>
                    <span>Sign Out</span>
                </button>
            </form>
        </div>
        @endauth

        <div class="sidebar-background"></div>
    </div><!-- /app-menu -->

    <div class="vertical-overlay" id="vertical-overlay"></div>

    <!-- ========== TOPBAR ========== -->
    <header id="page-topbar">
        <div class="layout-width">
            <div class="navbar-header">
                <div class="d-flex">
                    <div class="navbar-brand-box horizontal-logo">
                        <a href="{{ route('poultry.overview') }}" class="logo logo-dark">
                            <span class="logo-sm"><span class="pl-logo-mark @if ($brandLogo) has-img @endif">@if ($brandLogo)<img src="{{ $brandLogo }}" alt="">@else<i class="ri-seedling-fill"></i>@endif</span></span>
                            <span class="logo-lg"><span class="pl-logo"><span class="pl-logo-mark @if ($brandLogo) has-img @endif">@if ($brandLogo)<img src="{{ $brandLogo }}" alt="">@else<i class="ri-seedling-fill"></i>@endif</span><span class="pl-logo-text">{{ $farmName }}</span></span></span>
                        </a>
                    </div>
                    <button type="button" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger shadow-none" id="topnav-hamburger-icon">
                        <span class="hamburger-icon"><span></span><span></span><span></span></span>
                    </button>
                    <div class="d-none d-md-inline-flex align-items-center" style="position:relative;">
                        <button type="button" id="spotlight-trigger" style="display:flex;align-items:center;gap:8px;border-radius:10px;padding:7px 14px;cursor:pointer;transition:all .2s;min-width:220px;">
                            <i class="mdi mdi-magnify" style="font-size:16px;opacity:.8;"></i>
                            <span style="font-size:13px;flex:1;text-align:left;">Search everything…</span>
                            <div style="display:flex;gap:4px;"><kbd style="font-size:10px;padding:2px 6px;border-radius:4px;">⌘</kbd><kbd style="font-size:10px;padding:2px 6px;border-radius:4px;">K</kbd></div>
                        </button>
                        <div class="search-tooltip">Press <kbd>⌘K</kbd> or <kbd>Ctrl+K</kbd> to search</div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-1">
                    <!-- Theme Toggle -->
                    <div class="position-relative" id="theme-toggle-wrapper">
                        <button type="button" id="theme-toggle-btn" class="btn btn-icon btn-topbar btn-ghost-dark rounded-circle" style="width:38px;height:38px;" title="Theme" aria-label="Theme" aria-haspopup="menu">
                            <i id="theme-icon" class="ri-sun-line align-middle fs-3xl"></i>
                        </button>
                        <div id="theme-dropdown" style="display:none;position:absolute;top:calc(100% + 8px);right:0;min-width:210px;background:var(--tb-secondary-bg,#fff);border:1px solid var(--tb-border-color,#e9ebec);border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:9999;overflow:hidden;padding:6px;" role="menu">
                            <div class="pt-menu-label">Appearance</div>
                            <a href="javascript:void(0)" class="theme-mode-item d-flex align-items-center gap-2 px-3 py-2 rounded-2 text-decoration-none" data-mode="light" role="menuitemradio"><i class="ri-sun-line"></i> Light<i class="ri-check-line pt-check"></i></a>
                            <a href="javascript:void(0)" class="theme-mode-item d-flex align-items-center gap-2 px-3 py-2 rounded-2 text-decoration-none" data-mode="dark" role="menuitemradio"><i class="ri-moon-line"></i> Dark<i class="ri-check-line pt-check"></i></a>
                            <a href="javascript:void(0)" class="theme-mode-item d-flex align-items-center gap-2 px-3 py-2 rounded-2 text-decoration-none" data-mode="auto" role="menuitemradio"><i class="ri-contrast-2-line"></i> Auto <small>(system)</small><i class="ri-check-line pt-check"></i></a>
                            <div class="pt-menu-sep"></div>
                            <div class="pt-menu-label">Style</div>
                            <a href="javascript:void(0)" class="theme-mode-item d-flex align-items-center gap-2 px-3 py-2 rounded-2 text-decoration-none" data-ui="default" role="menuitemradio"><i class="ri-layout-grid-line"></i> Default<i class="ri-check-line pt-check"></i></a>
                            <a href="javascript:void(0)" class="theme-mode-item d-flex align-items-center gap-2 px-3 py-2 rounded-2 text-decoration-none" data-ui="apple" role="menuitemradio"><i class="ri-apple-line"></i> Apple<i class="ri-check-line pt-check"></i></a>
                        </div>
                    </div>

                    <!-- ===== USER DROPDOWN ===== -->
                    @auth
                    @php
                        $userdata = Auth::user();
                        $fullName = $userdata->name ?? 'User';
                        $initials = collect(explode(' ', trim($fullName)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('');
                        $srcPath = $userdata->profile_image && \Illuminate\Support\Facades\Storage::disk('public')->exists($userdata->profile_image)
                            ? asset('storage/' . $userdata->profile_image) : null;
                        $userRoles = $userdata->roles->pluck('name');
                        $roleColors = [
                            'super admin'   => ['#405189', '#eef2ff'],
                            'admin'         => ['#405189', '#eef2ff'],
                            'farm manager'  => ['#0a9396', '#e0f2fe'],
                            'record keeper' => ['#2a9d8f', '#e6f7f5'],
                            'sales officer' => ['#e76f51', '#fff0ed'],
                            'accountant'    => ['#6a0572', '#f3e8ff'],
                            'viewer'        => ['#457b9d', '#e8f0fe'],
                        ];
                    @endphp
                    <div class="dropdown position-relative ms-sm-3 header-item topbar-user" id="user-dropdown-wrapper">
                        <button type="button" id="user-menu-btn" class="btn shadow-none p-0" style="background:transparent;border:none;">
                            <span class="d-flex align-items-center gap-2">
                                <span style="display:inline-block;width:42px;height:42px;flex-shrink:0;position:relative;">
                                    @if ($srcPath)
                                        <img id="topbar-avatar-img" src="{{ $srcPath }}" alt="{{ $fullName }}" style="width:42px;height:42px;border-radius:10px;object-fit:cover;" onerror="this.style.display='none';document.getElementById('topbar-avatar-fallback').style.display='flex';">
                                        <span id="topbar-avatar-fallback" style="display:none;width:42px;height:42px;border-radius:10px;background:#405189;color:#fff;align-items:center;justify-content:center;">{{ $initials }}</span>
                                    @else
                                        <span style="display:flex;width:42px;height:42px;border-radius:10px;background:#405189;color:#fff;align-items:center;justify-content:center;">{{ $initials }}</span>
                                    @endif
                                </span>
                                <span class="d-none d-xl-flex flex-column align-items-start ms-1"><span class="fw-medium" style="font-size:13px;">{{ $userdata->name }}</span></span>
                            </span>
                        </button>

                        <div id="user-dropdown" class="dropdown-menu dropdown-menu-end" style="display:none;position:absolute;top:calc(100% + 8px);right:0;min-width:220px;background:var(--vz-dropdown-bg,#fff);border-radius:12px;z-index:9999;">
                            <div class="dropdown-header"><h6 class="mb-0">Welcome back!</h6><small class="text-muted">{{ $userdata->name }}</small></div>
                            <div class="dropdown-divider"></div>
                            <div class="px-3 py-2">
                                <div class="small text-muted mb-2 text-uppercase" style="font-size:10px;">Your Roles</div>
                                <div class="d-flex flex-wrap gap-1">
                                    @forelse ($userRoles as $roleName)
                                        @php [$rc, $rbg] = $roleColors[strtolower($roleName)] ?? ['#6c757d', '#f8fafc']; @endphp
                                        <span style="display:inline-block;font-size:10px;font-weight:600;padding:3px 10px;border-radius:20px;background:{{ $rbg }};color:{{ $rc }};">{{ $roleName }}</span>
                                    @empty
                                        <span class="text-muted small">No role yet</span>
                                    @endforelse
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{ route('users.overview', $userdata->id) }}"><i class="mdi mdi-account-circle me-2"></i>My Profile</a>
                            <a class="dropdown-item" href="{{ route('user.settings', $userdata->id) }}"><i class="mdi mdi-cog me-2"></i>Account Settings</a>
                            <div class="dropdown-divider"></div>
                            <form method="POST" action="{{ route('logout') }}" id="topbar-logout-form">@csrf<a class="dropdown-item text-danger" href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('topbar-logout-form').submit();"><i class="mdi mdi-logout me-2"></i>Logout</a></form>
                        </div>
                    </div>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Image View Modal -->
    <div class="modal fade" id="imageViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title">Profile Photo</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <img id="enlargedImage" src="" alt="Profile" class="img-fluid rounded-3" style="max-height:400px;">
                </div>
            </div>
        </div>
    </div>

    @yield('content')

    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    {{ now()->year }} © {{ $farmName }}
                    <span class="text-muted">· Powered by <span class="fw-semibold">Poultrilic</span></span>
                </div>
                <div class="col-sm-6">
                    <div class="text-sm-end">
                        Created by
                        <a href="https://qudroids.com" target="_blank" rel="noopener noreferrer" class="fw-semibold">Qudroid Systems</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</div>

<button class="btn btn-dark btn-icon" id="back-to-top"><i class="bi bi-caret-up fs-3xl"></i></button>
<div id="preloader"><div id="status"><div class="spinner-border text-primary avatar-sm" role="status"><span class="visually-hidden">Loading...</span></div></div></div>

@include('layouts.inc.customizer')

<!-- =====================================================
     SCRIPTS — core libraries load once here; page includes add only their own.
     ===================================================== -->
<script src="{{ asset('theme/layouts/assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('theme/layouts/assets/js/app.js') }}"></script>
<script src="{{ asset('theme/layouts/assets/libs/simplebar/simplebar.min.js') }}"></script>
<script src="{{ asset('theme/layouts/assets/js/plugins.js') }}"></script>
<script src="{{ asset('theme/layouts/assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

@include('layouts.inc.layout-scripts')

<!-- Route-specific JS -->
@if (Route::is('dashboard'))
    @include('layouts.pages-assets.js.dashboard-list-js')
@endif
@if (Route::is('users.*', 'user.*'))
    @include('layouts.pages-assets.js.users-list-js')
@endif
@if (Route::is('roles.*'))
    @include('layouts.pages-assets.js.role-list-js')
@endif
@if (Route::is('permissions.*'))
    @include('layouts.pages-assets.js.permissions-list-js')
@endif
@if (Route::is('week-entries.*'))
    @include('layouts.pages-assets.js.week-list-js')
@endif
@if (Route::is('daily-entries.*'))
    @include('layouts.pages-assets.js.daily-list-js')
@endif
@if (Route::is('expenses.*', 'settings.*', 'feed.*', 'sales.*', 'customers.*'))
    @include('layouts.pages-assets.js.finance-js')
@endif
@if (Route::is('flocks.*', 'poultry.*', 'imports.*', 'livestock.*', 'reports.*'))
    @include('layouts.pages-assets.js.app-js')
@endif
@stack('scripts')

{{-- Apple-style dialogs (SweetAlert2), confirmations, toasts and Spotlight (⌘K). --}}
@include('partials.apple-alert')
@include('layouts.inc.ui-scripts')
</body>
</html>
