{{-- Page hero in the cb-ui look.
     @include('partials.page-header', ['title' => ..., 'subtitle' => ..., 'icon' => 'ri-…',
        'crumbs' => ['Poultry' => route(...), 'Import' => null], 'actions' => '<a class="btn …">…</a>', 'pills' => ['…']]) --}}
<div class="cb-hero pl-hero">
    @isset($crumbs)
        <nav class="pl-crumbs" aria-label="Breadcrumb">
            @foreach ($crumbs as $label => $url)
                @if ($url && ! $loop->last)
                    <a href="{{ $url }}">{{ $label }}</a><i class="ri-arrow-right-s-line"></i>
                @else
                    <span>{{ $label }}</span>
                @endif
            @endforeach
        </nav>
    @endisset
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div>
            <h1>@isset($icon)<i class="{{ $icon }} me-2"></i>@endisset{{ $title }}</h1>
            @if (! empty($subtitle))<p>{{ $subtitle }}</p>@endif
        </div>
        @if (! empty($actions))
            <div class="cb-hero-actions">{!! $actions !!}</div>
        @endif
    </div>
    @if (! empty($pills))
        <div class="meta-pills">@foreach ($pills as $pill)<span class="cb-meta-pill">{!! $pill !!}</span>@endforeach</div>
    @endif
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
