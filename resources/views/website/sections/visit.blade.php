{{-- Visit / find us: hours, address, phone, map (when set). --}}
@php $map = $site['map_embed'] ?? null; @endphp
<section class="section visit-sec">
    <div class="container visit">
        <div class="visit-card reveal from-left">
            <span class="eyebrow">Visit the farm</span>
            <h2>Come and see where your food comes from</h2>
            <ul class="visit-list" data-stagger>
                @if ($farm->address)<li><i class="ri-map-pin-2-line"></i><div><b>Where</b>{{ $farm->address }}</div></li>@endif
                <li><i class="ri-time-line"></i><div><b>When</b>{{ $site['office_hours'] ?? 'By appointment — book a visit first' }}</div></li>
                @foreach (array_slice($farm->phones, 0, 2) as $ph)<li><i class="ri-phone-line"></i><div><b>Call</b><a href="tel:{{ preg_replace('/\s+/', '', $ph) }}">{{ $ph }}</a></div></li>@endforeach
                @if ($farm->email)<li><i class="ri-mail-line"></i><div><b>E-mail</b><a href="mailto:{{ $farm->email }}">{{ $farm->email }}</a></div></li>@endif
            </ul>
            <a class="btn btn-primary" href="{{ route('site.contact') }}#visit">Book a visit <i class="ri-calendar-event-line"></i></a>
        </div>
        <div class="visit-map reveal from-right d2">
            @if ($map && str_contains($map, '<iframe'))
                <div class="map">{!! $map !!}</div>
            @else
                <div class="map-art" aria-hidden="true">
                    <span class="pin"><i class="ri-map-pin-2-fill"></i></span>
                    <span class="ring r1"></span><span class="ring r2"></span><span class="ring r3"></span>
                    <span class="road a"></span><span class="road b"></span>
                    <b>{{ $farm->name }}</b>
                </div>
            @endif
        </div>
    </div>
</section>
