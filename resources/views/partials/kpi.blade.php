{{-- @include('partials.kpi', ['label' => 'Eggs laid', 'value' => '12,345', 'sub' => '...', 'icon' => 'ri-...', 'tone' => 'primary']) --}}
<div class="card card-animate h-100 mb-0">
    <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
            <div class="me-2">
                <p class="text-uppercase fw-medium text-muted fs-12 mb-2">{{ $label }}</p>
                <h4 class="fs-20 fw-semibold mb-1">{{ $value }}</h4>
                @if (! empty($sub))<p class="text-muted fs-12 mb-0">{!! $sub !!}</p>@endif
            </div>
            @if (! empty($icon))
                <div class="avatar-sm flex-shrink-0">
                    <span class="avatar-title bg-{{ $tone ?? 'primary' }}-subtle text-{{ $tone ?? 'primary' }} rounded fs-3"><i class="{{ $icon }}"></i></span>
                </div>
            @endif
        </div>
    </div>
</div>
