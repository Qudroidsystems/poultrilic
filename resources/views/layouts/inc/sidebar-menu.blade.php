{{-- Sidebar menu: grouped by the job people come to do, each link shown only with its permission. --}}
@php
    $u = auth()->user();
    $active = fn (...$p) => request()->routeIs(...$p) ? 'active' : '';
    $livestockTypes = \Illuminate\Support\Facades\Schema::hasTable('livestock_types')
        ? \App\Models\LivestockType::where('active', true)->orderBy('sort')->get() : collect();
@endphp
<ul class="navbar-nav" id="navbar-nav">
    <li class="menu-title"><span>Farm</span></li>
    @can('View flock')
        <li class="nav-item"><a class="nav-link menu-link {{ $active('poultry.overview') }}" href="{{ route('poultry.overview') }}"><i class="ri-dashboard-2-line"></i> <span>Farm overview</span></a></li>
    @endcan
    @can('dashboard')
        <li class="nav-item"><a class="nav-link menu-link {{ $active('dashboard') }}" href="{{ route('dashboard') }}"><i class="ph-gauge"></i> <span>Analytics dashboard</span></a></li>
    @endcan

    @can('View flock')
        <li class="menu-title"><i class="ri-more-fill"></i> <span>Poultry</span></li>
        <li class="nav-item"><a class="nav-link menu-link {{ $active('flocks.*', 'week-entries.*', 'daily-entries.*', 'poultry.flocks.*') }}" href="{{ route('flocks.index') }}"><i class="ri-home-4-line"></i> <span>Flocks & daily records</span></a></li>
        @can('Merge flock')
            <li class="nav-item"><a class="nav-link menu-link {{ $active('poultry.merge*') }}" href="{{ route('poultry.merge') }}"><i class="ri-git-merge-line"></i> <span>Combine flocks</span></a></li>
        @endcan
    @endcan
    @can('Import records')
        <li class="nav-item"><a class="nav-link menu-link {{ $active('imports.*') }}" href="{{ route('imports.index') }}"><i class="ri-file-excel-2-line"></i> <span>Import from Excel</span></a></li>
    @endcan

    @if ($livestockTypes->isNotEmpty() || $u?->can('Manage livestock types'))
        <li class="menu-title"><i class="ri-more-fill"></i> <span>Livestock & more</span></li>
        @can('View livestock')
            @foreach ($livestockTypes as $t)
                <li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('livestock.index', 'livestock.units.*') && optional(request()->route('type'))->id === $t->id ? 'active' : '' }}" href="{{ route('livestock.index', $t) }}"><i class="{{ $t->icon }}"></i> <span>{{ $t->name }}</span></a></li>
            @endforeach
        @endcan
        @can('Manage livestock types')
            <li class="nav-item"><a class="nav-link menu-link {{ $active('livestock.types.*') }}" href="{{ route('livestock.types.index') }}"><i class="ri-add-circle-line"></i> <span>{{ $livestockTypes->isEmpty() ? 'Add cattle, goats, fish…' : 'Set up farm units' }}</span></a></li>
        @endcan
    @endif

    @can('View reports')
        <li class="menu-title"><i class="ri-more-fill"></i> <span>Reports</span></li>
        <li class="nav-item"><a class="nav-link menu-link {{ $active('reports.performance') }}" href="{{ route('reports.performance') }}"><i class="ri-file-chart-line"></i> <span>Performance report</span></a></li>
        <li class="nav-item"><a class="nav-link menu-link {{ request()->routeIs('reports.*') && ! request()->routeIs('reports.performance') ? 'active' : '' }}" href="{{ route('reports.index') }}"><i class="ri-bar-chart-2-line"></i> <span>Reports & analytics</span></a></li>
    @endcan

    @canany(['View sale', 'View customer'])
        <li class="menu-title"><i class="ri-more-fill"></i> <span>Sales</span></li>
        @can('View sale')<li class="nav-item"><a class="nav-link menu-link {{ $active('sales.*') }}" href="{{ route('sales.index') }}"><i class="ph-receipt"></i> <span>Invoices</span></a></li>@endcan
        @can('View customer')<li class="nav-item"><a class="nav-link menu-link {{ $active('customers.*') }}" href="{{ route('customers.index') }}"><i class="ri-team-line"></i> <span>Customers</span></a></li>@endcan
    @endcanany

    @canany(['View expense', 'View feed', 'View settings'])
        <li class="menu-title"><i class="ri-more-fill"></i> <span>Finance</span></li>
        @can('View expense')<li class="nav-item"><a class="nav-link menu-link {{ $active('expenses.*') }}" href="{{ route('expenses.index') }}"><i class="ph-coins"></i> <span>Expenses</span></a></li>@endcan
        @can('View feed')<li class="nav-item"><a class="nav-link menu-link {{ $active('feed.*') }}" href="{{ route('feed.index') }}"><i class="ri-stack-line"></i> <span>Feed stock</span></a></li>@endcan
        @can('View settings')<li class="nav-item"><a class="nav-link menu-link {{ $active('settings.*') }}" href="{{ route('settings.index') }}"><i class="ri-price-tag-3-line"></i> <span>Prices & costs</span></a></li>@endcan
    @endcanany

    <li class="menu-title"><i class="ri-more-fill"></i> <span>Admin</span></li>
    @can('View user')<li class="nav-item"><a class="nav-link menu-link {{ $active('users.*') }}" href="{{ route('users.index') }}"><i class="ph-user-circle"></i> <span>Users</span></a></li>@endcan
    @can('View role')<li class="nav-item"><a class="nav-link menu-link {{ $active('roles.*') }}" href="{{ route('roles.index') }}"><i class="ph-address-book"></i> <span>Roles</span></a></li>@endcan
    @can('View permission')<li class="nav-item"><a class="nav-link menu-link {{ $active('permissions.*') }}" href="{{ route('permissions.index') }}"><i class="ri-shield-keyhole-line"></i> <span>Permissions</span></a></li>@endcan
    <li class="nav-item"><a class="nav-link menu-link" href="{{ $u ? route('users.overview', $u->id) : '#' }}"><i class="ri-account-circle-line"></i> <span>My account</span></a></li>
</ul>
