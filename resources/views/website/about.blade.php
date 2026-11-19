@extends('website.layout', ['pageTitle' => 'About us'])

@php
    $s = fn ($k, $d = null) => (isset($site[$k]) && $site[$k] !== '') ? $site[$k] : $d;
    $F = fn ($p) => \App\Services\Website\WebsiteContent::fileUrl($p);
    $poultry = $enterprises->firstWhere('name', 'Poultry');
    $others = $enterprises->where('name', '!=', 'Poultry')->pluck('name');
    $photo = $F($s('intro_image')) ?: $F($s('hero_image'));
@endphp

@section('content')
    @include('website.partials.page-hero', ['title' => 'About ' . $farm->name, 'lead' => $farm->tagline ?: 'A working farm that counts every egg, every bird and every bag of feed.', 'crumbs' => [['About', null]], 'image' => $photo])

    <div class="subnav reveal">
        <a href="#story">Our story</a><a href="#how">How we farm</a><a href="#today">The farm today</a>
        @if ($people->count())<a href="#team">Our team</a>@endif
        <a href="#visit">Visit us</a>
    </div>

    {{-- Story: the farm's own About page text when written, otherwise a short introduction from the records --}}
    <section class="section" id="story">
        <span class="blob" style="width:320px;height:320px;right:-120px;top:10%;background:var(--accent)"></span>
        <div class="container split">
            <div class="reveal from-left">
                <span class="eyebrow">Our story</span>
                <h2>{{ $page->title ?? $s('intro_title', 'Good food starts with good farming') }}</h2>
                @if ($page)
                    <div class="prose">{!! $page->body !!}</div>
                @elseif ($s('intro_body'))
                    <div class="prose lead">{!! $s('intro_body') !!}</div>
                @else
                    @php
                        $story = $farm->name . ' is a working farm' . ($farm->address ? ' in ' . $farm->address : '') . '.';
                        if ($poultry && $poultry->count) {
                            $story .= ' Today we look after ' . number_format($poultry->count) . ' birds in ' . $poultry->units . ' ' . $poultry->unit_label
                                . ($others->count() ? ', alongside ' . $others->map(fn ($o) => strtolower($o))->join(', ', ' and ') : '') . '.';
                        }
                    @endphp
                    <p class="lead">{{ $story }}</p>
                    <p>Our team records feed, health, production and sales every day. That discipline is what keeps our birds healthy and our eggs fresh — and it is why the numbers you see on this website are real.</p>
                @endif
                @if ($s('founded_year'))
                    <div class="timeline-mini" data-stagger>
                        <div><b>{{ $s('founded_year') }}</b><span>Farming started</span></div>
                        <div><b>{{ max(1, now()->year - (int) $s('founded_year')) }}+ yrs</b><span>of experience</span></div>
                        <div><b>{{ now()->year }}</b><span>Still growing</span></div>
                    </div>
                @endif
            </div>
            <div class="reveal from-right d2">
                @if ($photo)
                    <div class="media-frame"><img src="{{ $photo }}" alt="{{ $farm->name }}" loading="lazy" data-parallax="-0.04"></div>
                @else
                    <div class="egg-stage" aria-hidden="true">
                        <div class="big-egg"><span class="shine-dot"></span></div>
                        <span class="orbit o1"><i class="ri-seedling-line"></i> Healthy birds</span>
                        <span class="orbit o2"><i class="ri-calendar-check-line"></i> Collected daily</span>
                        <span class="orbit o3"><i class="ri-file-list-3-line"></i> Every day on record</span>
                        <span class="orbit o4"><i class="ri-truck-line"></i> To your table</span>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <div id="how">@include('website.sections.process', ['tone' => 'cream', 'title' => 'How we farm, every day'])</div>
    <div id="today">
        @include('website.sections.trend', ['title' => 'The farm today, in numbers'])
        @include('website.partials.stats', ['stats' => $stats])
    </div>
    @include('website.sections.enterprises', ['enterprises' => $enterprises, 'tone' => 'soft'])
    @include('website.sections.values', ['tone' => ''])

    @if ($people->count())
        <section class="section soft" id="team">
            <div class="container">
                <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:20px;flex-wrap:wrap;margin-bottom:36px" class="reveal">
                    <div><span class="eyebrow">Our team</span><h2 style="margin:0">The people behind the farm</h2></div>
                    <a class="link-arrow" href="{{ route('site.team') }}">Meet everyone <i class="ri-arrow-right-line"></i></a>
                </div>
                <div class="grid-4" data-stagger>
                    @foreach ($people as $p)
                        <div class="person" data-tilt>
                            <div class="ph">@if ($p->photo)<img src="{{ $F($p->photo) }}" alt="{{ $p->name }}" loading="lazy">@else<span class="initials">{{ collect(explode(' ', $p->name))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->join('') }}</span>@endif</div>
                            <h4>{{ $p->name }}</h4>@if ($p->role)<small>{{ $p->role }}</small>@endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($more->count())
        <section class="section" style="padding-top:60px;padding-bottom:60px">
            <div class="container">
                <div class="chips" style="margin:0">@foreach ($more as $pg)<a class="chip" href="{{ route('site.page', $pg->slug) }}">{{ $pg->title }} <i class="ri-arrow-right-up-line"></i></a>@endforeach</div>
            </div>
        </section>
    @endif

    <div id="visit">@include('website.sections.visit')</div>
    @include('website.sections.cta')
@endsection
