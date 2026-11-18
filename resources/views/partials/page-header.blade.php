{{-- @include('partials.page-header', ['title' => ..., 'crumbs' => ['Poultry' => route(...), 'Import' => null], 'actions' => '...html']) --}}
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <div>
                <h4 class="mb-sm-0">{{ $title }}</h4>
                @isset($subtitle)<p class="text-muted mb-0 mt-1 fs-13">{{ $subtitle }}</p>@endisset
            </div>
            <div class="page-title-right d-flex align-items-center gap-3">
                @isset($actions){!! $actions !!}@endisset
                @isset($crumbs)
                    <ol class="breadcrumb m-0 d-none d-md-flex">
                        @foreach ($crumbs as $label => $url)
                            @if ($url && ! $loop->last)
                                <li class="breadcrumb-item"><a href="{{ $url }}">{{ $label }}</a></li>
                            @else
                                <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}">{{ $label }}</li>
                            @endif
                        @endforeach
                    </ol>
                @endisset
            </div>
        </div>
    </div>
</div>
@if ($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
@endif
@if (session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">{{ session('warning') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
@endif
