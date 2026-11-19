@extends('website.layout')

@php
    $s = fn ($k, $d = null) => (isset($site[$k]) && $site[$k] !== '') ? $site[$k] : $d;
    $F = fn ($p) => \App\Services\Website\WebsiteContent::fileUrl($p);
    $W = \App\Services\Website\WebsiteContent::class;
    $ordersOpen = $s('orders_open', '1') === '1';
    $products = $W::productsOrEggs(6);
    $heroImage = $F($s('hero_image')) ?: ($albums->first()?->coverPhoto() ? $F($albums->first()->coverPhoto()->path) : null);
    $heroSlides = $slides->count() ? $slides : collect([(object) [
        'title' => $s('hero_title', $farm->tagline ?: 'Fresh from ' . $farm->name),
        'subtitle' => $s('hero_subtitle', 'Eggs collected every day, healthy birds and honest prices — straight from our farm to your table.'),
        'image' => $s('hero_image'),
        'button_text' => $s('hero_button_text', 'See products & prices'),
        'button_link' => $s('hero_button_link', route('site.products')),
    ]]);
    $photos = $albums->flatMap(fn ($a) => $a->photos->take(3)->map(fn ($p) => ['p' => $p, 'a' => $a]))->take(8);
    $heroStats = collect($stats)->take(3);
    $tickerItems = $highlights->count() ? $highlights->pluck('title')
        : $enterprises->pluck('name')->merge(['Collected daily', 'Pick-up or delivery', 'Bulk supply'])->unique();
    $poultry = $enterprises->firstWhere('name', 'Poultry');
    $others = $enterprises->where('name', '!=', 'Poultry')->pluck('name');
    $firstPhoto = $heroImage;
@endphp

@section('content')
    {{-- 1. Hero: the farm's slides, or an animated farm scene with eggs when no photo has been added --}}
    <section class="hero {{ $heroImage || $slides->count() ? '' : 'no-photo' }}" aria-label="Welcome">
        @foreach ($heroSlides as $i => $sl)
            @php $img = $F($sl->image) ?: $heroImage; @endphp
            <div class="slide {{ $i === 0 ? 'on' : '' }}">
                @if ($img)
                    <div class="bg" style="background-image:url('{{ $img }}')"></div>
                @else
                    <div class="scene" aria-hidden="true">
                        <span class="sun"></span>
                        <i class="ri-leaf-line leaf" style="left:8%;top:22%"></i>
                        <i class="ri-plant-line leaf" style="left:46%;top:12%;animation-delay:-2s"></i>
                        <i class="ri-seedling-line leaf" style="left:70%;top:62%;animation-delay:-4s"></i>
                        <span class="hill h2"></span><span class="hill h1"></span>
                    </div>
                    <div class="egg-cluster" aria-hidden="true"><span class="glow"></span><span class="e e2"></span><span class="e e3"></span><span class="e e1"></span></div>
                @endif
                <div class="container">
                    <div class="content">
                        <span class="eyebrow" style="color:#fff">{{ $farm->name }}</span>
                        <h1 class="split-words">{{ $sl->title }}</h1>
                        @if ($sl->subtitle)<p class="reveal d2">{{ $sl->subtitle }}</p>@endif
                        <div class="actions reveal d3">
                            @if ($sl->button_text)<a class="btn btn-accent" href="{{ $sl->button_link ?: route('site.products') }}">{{ $sl->button_text }} <i class="ri-arrow-right-line"></i></a>@endif
                            <a class="btn btn-ghost" href="{{ route('site.about') }}">Our story</a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
        @if ($heroStats->count())
            <div class="live-card" aria-label="Live from the farm">
                <span class="live-dot" style="margin-bottom:6px">Live from the farm</span>
                @foreach ($heroStats as $st)
                    <div class="row"><span>{{ $st['label'] }}</span><b>{{ number_format($st['value']) }}{{ $st['suffix'] }}</b></div>
                @endforeach
            </div>
        @endif
        @if ($heroSlides->count() > 1)
            <div class="dots">@foreach ($heroSlides as $i => $sl)<button class="{{ $i === 0 ? 'on' : '' }}" aria-label="Slide {{ $i + 1 }}"></button>@endforeach</div>
        @endif
    </section>

    {{-- 2. Our promise (trust badges) --}}
    @include('website.sections.promise')

    {{-- 3. Ticker --}}
    @if ($tickerItems->count())
        <div class="ticker marquee" style="margin-top:60px" aria-hidden="true">
            <div class="track">
                @foreach (range(1, 3) as $rep)
                    @foreach ($tickerItems as $t)<span><i class="ri-sparkling-2-fill"></i>{{ $t }}</span>@endforeach
                @endforeach
            </div>
        </div>
    @endif

    {{-- 4. Welcome: the farm's own text, or a short introduction built from the farm records --}}
    <section class="section">
        <span class="blob" style="width:300px;height:300px;left:-120px;top:20%;background:var(--accent)"></span>
        <div class="container split">
            <div class="reveal from-left">
                <span class="eyebrow">Welcome to {{ $farm->name }}</span>
                <h2>{{ $s('intro_title', 'Good food starts with good farming') }}</h2>
                @if ($s('intro_body'))
                    <div class="prose lead">{!! $s('intro_body') !!}</div>
                @else
                    @php
                        $story = ($poultry && $poultry->count)
                            ? 'We look after ' . number_format($poultry->count) . ' birds in ' . $poultry->units . ' ' . $poultry->unit_label
                              . ($others->count() ? ', alongside ' . $others->map(fn ($o) => strtolower($o))->join(', ', ' and ') : '') . '. '
                            : '';
                    @endphp
                    <p class="lead">
                        {{ $story }}Every egg collected, every bag of feed and every bird is recorded by our team each day — so what you buy from us is fresh and properly accounted for.
                    </p>
                @endif
                <ul class="checks" data-stagger>
                    @foreach (($highlights->count() ? $highlights : $W::itemsOr('promise'))->take(4) as $h)
                        <li><span><b style="color:var(--ink)">{{ $h->title }}</b>@if ($h->body) — {{ $h->body }}@endif</span></li>
                    @endforeach
                </ul>
                <div style="display:flex;gap:12px;flex-wrap:wrap">
                    <a class="btn btn-primary" href="{{ route('site.about') }}">About the farm</a>
                    <a class="btn btn-outline" href="{{ route('site.contact') }}#visit">Book a visit</a>
                </div>
            </div>
            @php $intro = $F($s('intro_image')) ?: $firstPhoto; @endphp
            <div class="reveal from-right d2">
                @if ($intro)
                    <div class="media-frame">
                        <img src="{{ $intro }}" alt="{{ $farm->name }}" loading="lazy" data-parallax="-0.04">
                        @if ($s('founded_year'))<div class="badge"><b>{{ max(1, now()->year - (int) $s('founded_year')) }}+</b>years of farming</div>@endif
                    </div>
                @else
                    <div class="egg-stage" aria-hidden="true">
                        <div class="big-egg"><span class="shine-dot"></span></div>
                        @foreach ($heroStats->take(3) as $i => $st)
                            <span class="orbit o{{ $i + 1 }}"><i class="{{ $st['icon'] }}"></i> {{ number_format($st['value']) }}{{ $st['suffix'] }} <small style="color:var(--muted);font-weight:500">{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::limit($st['label'], 22, '')) }}</small></span>
                        @endforeach
                        @if ($s('founded_year'))<span class="orbit o4"><i class="ri-calendar-check-line"></i> since {{ $s('founded_year') }}</span>@endif
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- 5. Products with today's prices --}}
    @if ($products->count())
        <section class="section soft">
            <span class="blob" style="width:380px;height:380px;right:-140px;top:10%;background:var(--primary)"></span>
            <div class="container">
                <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:20px;flex-wrap:wrap;margin-bottom:40px" class="reveal">
                    <div><span class="eyebrow">Fresh from the farm</span><h2 style="margin:0">Products &amp; today's prices</h2></div>
                    <a class="link-arrow" href="{{ route('site.products') }}">All products <i class="ri-arrow-right-line"></i></a>
                </div>
                <div class="products">
                    @foreach ($products as $i => $p)
                        @include('website.partials.product-card', ['p' => $p, 'delay' => $i % 3 + 1, 'ordersOpen' => $ordersOpen])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- 6. Live egg chart --}}
    @include('website.sections.trend')

    {{-- 7. Farm to table --}}
    @include('website.sections.process', ['tone' => 'cream'])

    {{-- 8. What we raise (live) --}}
    @include('website.sections.enterprises', ['enterprises' => $enterprises])

    {{-- 9. Live numbers --}}
    @include('website.partials.stats', ['stats' => $stats])

    {{-- 10. Values --}}
    @include('website.sections.values', ['tone' => ''])

    {{-- 11. Why eggs --}}
    @include('website.sections.nutrition')

    {{-- 12. Bulk buyers --}}
    @include('website.sections.bulk')

    {{-- 13. Gallery --}}
    @if ($photos->count())
        <section class="section soft">
            <div class="container">
                <div class="section-head center reveal">
                    <span class="eyebrow">Gallery</span>
                    <h2>Life on {{ $farm->name }}</h2>
                </div>
                <div class="masonry reveal zoom">
                    @foreach ($photos as $x)
                        <a href="{{ $F($x['p']->path) }}" data-lightbox="home" data-caption="{{ $x['p']->caption ?: $x['a']->title }}">
                            <img src="{{ $F($x['p']->thumb ?: $x['p']->path) }}" alt="{{ $x['p']->caption ?: $x['a']->title }}" loading="lazy">
                        </a>
                    @endforeach
                </div>
                <div class="center" style="margin-top:26px"><a class="btn btn-outline" href="{{ route('site.gallery') }}">View the full gallery</a></div>
            </div>
        </section>
    @endif

    {{-- 14. Testimonials --}}
    @if ($testimonials->count())
        <section class="section">
            <div class="container">
                <div class="section-head center reveal">
                    <span class="eyebrow">Customers</span>
                    <h2>What our customers say</h2>
                </div>
                <div class="quotes">
                    @foreach ($testimonials->take(6) as $i => $t)
                        <div class="quote reveal d{{ $i % 3 + 1 }}" data-tilt>
                            <p>{{ $t->body }}</p>
                            <div class="who">
                                @if ($t->image)<img src="{{ $F($t->image) }}" alt="">@else<span class="avatar">{{ mb_substr($t->title, 0, 1) }}</span>@endif
                                <div><b>{{ $t->title }}</b>@if ($t->subtitle)<small>{{ $t->subtitle }}</small>@endif</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- 15. News & events --}}
    @if ($news->count() || $events->count())
        <section class="section soft">
            <div class="container">
                <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:20px;flex-wrap:wrap;margin-bottom:40px" class="reveal">
                    <div><span class="eyebrow">News &amp; events</span><h2 style="margin:0">From the farm</h2></div>
                    <a class="link-arrow" href="{{ route('site.news') }}">All news <i class="ri-arrow-right-line"></i></a>
                </div>
                @if ($events->count())
                    <div class="events" style="margin-bottom:34px" data-stagger>
                        @foreach ($events as $e)
                            @php $d = \Carbon\Carbon::parse($e->date); @endphp
                            <a class="event" href="{{ $e->url }}">
                                <div class="date"><div><b>{{ $d->format('d') }}</b><small>{{ $d->format('M') }}</small></div></div>
                                <div><h4>{{ $e->title }}</h4><div class="where">{{ $d->format('l') }}{{ $e->time ? ' · ' . $e->time : '' }}{{ $e->location ? ' · ' . $e->location : '' }}</div></div>
                            </a>
                        @endforeach
                    </div>
                @endif
                @if ($news->count())
                    <div class="grid-3">
                        @foreach ($news as $i => $p)
                            @include('website.partials.post-card', ['p' => $p, 'delay' => $i % 3 + 1])
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- 16. Partners --}}
    @if ($partners->count())
        <section class="section" style="padding:60px 0">
            <div class="container">
                <p class="center eyebrow" style="display:flex;justify-content:center">Partners &amp; certifications</p>
                <div class="marquee partners-marquee">
                    <div class="track">
                        @foreach (range(1, 2) as $rep)
                            @foreach ($partners as $pt)
                                @if ($pt->link)<a href="{{ $pt->link }}" target="_blank" rel="noopener" title="{{ $pt->title }}" class="partners"><img src="{{ $F($pt->image) }}" alt="{{ $pt->title }}"></a>
                                @else<span class="partners"><img src="{{ $F($pt->image) }}" alt="{{ $pt->title }}" title="{{ $pt->title }}"></span>@endif
                            @endforeach
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- 17. FAQs --}}
    @include('website.sections.faq', ['tone' => 'cream', 'limit' => 6])

    {{-- 18. Visit the farm --}}
    @include('website.sections.visit')

    {{-- 19. Newsletter --}}
    @include('website.sections.newsletter')
@endsection
