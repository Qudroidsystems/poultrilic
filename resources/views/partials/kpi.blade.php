{{-- KPI tile in the cb-stat look.
     @include('partials.kpi', ['label' => 'Eggs laid', 'value' => '12,345', 'sub' => '...', 'icon' => 'ri-...', 'tone' => 'primary|success|warning|danger|info']) --}}
@php($accent = ['primary' => 'teal', 'success' => 'green', 'warning' => 'amber', 'danger' => 'rose', 'info' => 'sky', 'secondary' => 'violet'][$tone ?? 'primary'] ?? 'teal')
<div class="cb-stat pl-kpi accent-{{ $accent }}">
    <div class="stat-accent"></div>
    @if (! empty($icon))<div class="stat-ico"><i class="{{ $icon }}"></i></div>@endif
    <div class="stat-label">{{ $label }}</div>
    <div class="stat-value">{{ $value }}</div>
    @if (! empty($sub))<div class="stat-sub">{!! $sub !!}</div>@endif
</div>
