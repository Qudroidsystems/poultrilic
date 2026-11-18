{{-- Animated wave divider: @include('website.partials.wave', ['top' => '#fff', 'bottom' => 'var(--secondary)']) --}}
<div class="wave" aria-hidden="true" style="background:{{ $bottom ?? 'var(--secondary)' }}">
    <svg viewBox="0 0 1440 70" preserveAspectRatio="none"><path fill="{{ $top ?? '#fff' }}" d="M0,40 C180,80 360,0 540,30 C720,60 900,10 1080,30 C1260,50 1350,20 1440,40 L1440,0 L0,0 Z M1440,40 C1620,80 1800,0 1980,30 C2160,60 2340,10 2520,30 C2700,50 2790,20 2880,40 L2880,0 L1440,0 Z"/></svg>
</div>
