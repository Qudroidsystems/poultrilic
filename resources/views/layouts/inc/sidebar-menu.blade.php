{{-- Sidebar menu in the csskabba-portal structure: section titles, collapsible groups (menu-dropdown / nav-sm).
     The active item and its group are highlighted by layouts/inc/layout-scripts (initActiveSidebar). --}}
@php
    $u = auth()->user();
    $livestockTypes = \Illuminate\Support\Facades\Schema::hasTable('livestock_types')
        ? \App\Models\LivestockType::where('active', true)->orderBy('sort')->get() : collect();
@endphp
<ul class="navbar-nav" id="navbar-nav">

    <li class="menu-title"><span data-key="t-menu">Menu</span></li>

    {{-- Dashboards --}}
    @if ($u?->can('View flock') || $u?->can('dashboard'))
        <li class="nav-item">
            <a class="nav-link menu-link collapsed" href="#sidebarDashboards" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarDashboards">
                <i class="ph-gauge"></i> <span>Dashboards</span>
            </a>
            <div class="collapse menu-dropdown" id="sidebarDashboards">
                <ul class="nav nav-sm flex-column">
                    @can('View flock')<li class="nav-item"><a href="{{ route('poultry.overview') }}" class="nav-link">Farm Overview</a></li>@endcan
                    @can('dashboard')<li class="nav-item"><a href="{{ route('dashboard') }}" class="nav-link">Analytics Dashboard</a></li>@endcan
                </ul>
            </div>
        </li>
    @endif

    {{-- POULTRY --}}
    @if ($u?->can('View flock') || $u?->can('Import records'))
        <li class="menu-title"><i class="ri-more-fill"></i> <span>POULTRY</span></li>
        @can('View flock')
            <li class="nav-item">
                <a class="nav-link menu-link collapsed" href="#sidebarFlocks" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarFlocks">
                    <i class="ri-home-4-line"></i> <span>Flock Management</span>
                </a>
                <div class="collapse menu-dropdown" id="sidebarFlocks">
                    <ul class="nav nav-sm flex-column">
                        <li class="nav-item"><a href="{{ route('flocks.index') }}" class="nav-link">Flocks & Daily Records</a></li>
                        @can('Merge flock')<li class="nav-item"><a href="{{ route('poultry.merge') }}" class="nav-link">Combine Flocks</a></li>@endcan
                    </ul>
                </div>
            </li>
        @endcan
        @can('Import records')
            <li class="nav-item">
                <a class="nav-link menu-link" href="{{ route('imports.index') }}"><i class="ri-file-excel-2-line"></i> <span>Import from Excel</span></a>
            </li>
        @endcan
    @endif

    {{-- LIVESTOCK & MORE --}}
    @if (($livestockTypes->isNotEmpty() && $u?->can('View livestock')) || $u?->can('Manage livestock types'))
        <li class="menu-title"><i class="ri-more-fill"></i> <span>LIVESTOCK & MORE</span></li>
        <li class="nav-item">
            <a class="nav-link menu-link collapsed" href="#sidebarLivestock" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarLivestock">
                <i class="ri-leaf-line"></i> <span>Farm Units</span>
            </a>
            <div class="collapse menu-dropdown" id="sidebarLivestock">
                <ul class="nav nav-sm flex-column">
                    @can('View livestock')
                        @foreach ($livestockTypes as $t)
                            <li class="nav-item"><a href="{{ route('livestock.index', $t) }}" class="nav-link">{{ $t->name }}</a></li>
                        @endforeach
                    @endcan
                    @can('Manage livestock types')
                        <li class="nav-item"><a href="{{ route('livestock.types.index') }}" class="nav-link">{{ $livestockTypes->isEmpty() ? 'Add Cattle, Goats, Fish…' : 'Set Up Farm Units' }}</a></li>
                    @endcan
                </ul>
            </div>
        </li>
    @endif

    {{-- REPORTS --}}
    @can('View reports')
        <li class="menu-title"><i class="ri-more-fill"></i> <span>REPORTS & ANALYTICS</span></li>
        <li class="nav-item">
            <a class="nav-link menu-link collapsed" href="#sidebarReports" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarReports">
                <i class="ri-file-chart-line"></i> <span>Reports</span>
            </a>
            <div class="collapse menu-dropdown" id="sidebarReports">
                <ul class="nav nav-sm flex-column">
                    <li class="nav-item"><a href="{{ route('reports.performance') }}" class="nav-link">Performance Report</a></li>
                    <li class="nav-item"><a href="{{ route('reports.index') }}" class="nav-link">Reports & Analytics</a></li>
                    @can('Manage reports')<li class="nav-item"><a href="{{ route('reports.create') }}" class="nav-link">New Report</a></li>@endcan
                </ul>
            </div>
        </li>
    @endcan

    {{-- SALES & FINANCE --}}
    @canany(['View sale', 'View customer', 'View expense', 'View feed', 'View settings'])
        <li class="menu-title"><i class="ri-more-fill"></i> <span>SALES & FINANCE</span></li>
        @canany(['View sale', 'View customer'])
            <li class="nav-item">
                <a class="nav-link menu-link collapsed" href="#sidebarSales" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarSales">
                    <i class="ph-receipt"></i> <span>Sales</span>
                </a>
                <div class="collapse menu-dropdown" id="sidebarSales">
                    <ul class="nav nav-sm flex-column">
                        @can('View sale')<li class="nav-item"><a href="{{ route('sales.index') }}" class="nav-link">Invoices</a></li>@endcan
                        @can('View customer')<li class="nav-item"><a href="{{ route('customers.index') }}" class="nav-link">Customers</a></li>@endcan
                    </ul>
                </div>
            </li>
        @endcanany
        @canany(['View expense', 'View feed', 'View settings'])
            <li class="nav-item">
                <a class="nav-link menu-link collapsed" href="#sidebarFinance" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarFinance">
                    <i class="ph-coins"></i> <span>Finance</span>
                </a>
                <div class="collapse menu-dropdown" id="sidebarFinance">
                    <ul class="nav nav-sm flex-column">
                        @can('View expense')<li class="nav-item"><a href="{{ route('expenses.index') }}" class="nav-link">Expenses</a></li>@endcan
                        @can('View feed')<li class="nav-item"><a href="{{ route('feed.index') }}" class="nav-link">Feed Stock</a></li>@endcan
                        @can('View settings')<li class="nav-item"><a href="{{ route('settings.index') }}" class="nav-link">Prices & Costs</a></li>@endcan
                    </ul>
                </div>
            </li>
        @endcanany
    @endcanany

    {{-- USERS & PRIVILEGES --}}
    @if ($u?->can('View user') || $u?->can('View role') || $u?->can('View permission'))
        <li class="menu-title"><i class="ri-more-fill"></i> <span>USERS & PRIVILEGES</span></li>
    @endif
    @can('View user')
        <li class="nav-item">
            <a class="nav-link menu-link collapsed" href="#sidebarUsers" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarUsers">
                <i class="ph-user-circle"></i> <span>User Management</span>
            </a>
            <div class="collapse menu-dropdown" id="sidebarUsers">
                <ul class="nav nav-sm flex-column">
                    <li class="nav-item"><a href="{{ route('users.index') }}" class="nav-link">Users</a></li>
                </ul>
            </div>
        </li>
    @endcan
    @if ($u?->can('View role') || $u?->can('View permission'))
        <li class="nav-item">
            <a class="nav-link menu-link collapsed" href="#sidebarRoles" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarRoles">
                <i class="ri-shield-keyhole-line"></i> <span>Roles & Permissions</span>
            </a>
            <div class="collapse menu-dropdown" id="sidebarRoles">
                <ul class="nav nav-sm flex-column">
                    @can('View role')<li class="nav-item"><a href="{{ route('roles.index') }}" class="nav-link">Roles</a></li>@endcan
                    @can('View permission')<li class="nav-item"><a href="{{ route('permissions.index') }}" class="nav-link">Permissions</a></li>@endcan
                </ul>
            </div>
        </li>
    @endif

    {{-- My Account --}}
    @auth
        <li class="nav-item">
            <a class="nav-link menu-link collapsed" href="#sidebarAccount" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarAccount">
                <i class="ph-address-book"></i> <span>My Account</span>
            </a>
            <div class="collapse menu-dropdown" id="sidebarAccount">
                <ul class="nav nav-sm flex-column">
                    <li class="nav-item"><a href="{{ route('users.overview', auth()->id()) }}" class="nav-link"><i class="ri-profile-line me-2"></i> My Profile</a></li>
                    <li class="nav-item"><a href="{{ route('user.settings', auth()->id()) }}" class="nav-link"><i class="ri-settings-3-line me-2"></i> Account Settings</a></li>
                </ul>
            </div>
        </li>
    @endauth
</ul>
