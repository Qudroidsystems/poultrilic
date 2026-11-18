@extends('website.layout')

@php
    $s = fn ($k, $d = null) => (isset($site[$k]) && $site[$k] !== '') ? $site[$k] : $d;
    $F = fn ($p) => \App\Services\Website\WebsiteContent::fileUrl($p);
    $ordersOpen = $s('orders_open', '1') === '1';
    $heroImage = $F($s('hero_image')) ?: ($albums->first()?->coverPhoto() ? $F($albums->first()->coverPhoto()->path) : null);
    $heroSlides = $slides->count() ? $slides : collect([(object) [
        'title' => $s('hero_title', $farm->name),
        'subtitle' => $s('hero_subtitle', $farm->tagline),
        'image' => $s('hero_image'),
        'button_text' => $s('hero_button_text', $products->count() ? 'See products & prices' : 'Contact us'),
        'button_link' => $s('hero_button_link', $products->count() ? route('site.products') : route('site.contact')),
    ]]);
    $photos = $albums->flatMap(fn ($a) => $a->photos->take(3)->map(fn ($p) => ['p' => $p, 'a' => $a]))->take(8);
    $heroStats = collect($stats)->take(3);
    $tickerItems = $highlights->count() ? $highlights->pluck('title') : $enterprises->pluck('name');
@endphp

@section('content')
    {{-- Hero: the farm's slides, or an animated farm scene when no photos have been added --}}
    <section class="hero" aria-label="Welcome">
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
                @endif
                <div class="container">
                    <div class="content">
                        <span class="eyebrow" style="color:#fff">{{ $farm->name }}</span>
                        <h1 class="split-words">{{ $sl->title }}</h1>
                        @if ($sl->subtitle)<p>{{ $sl->subtitle }}</p>@endif
                        <div class="actions">
                            @if ($sl->button_text)<a class="btn btn-accent" href="{{ $sl->button_link ?: route('site.products') }}">{{ $sl->button_text }} <i class="ri-arrow-right-line"></i></a>@endif
                            <a class="btn btn-ghost" href="{{ route('site.contact') }}#visit">Visit the farm</a>
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

    {{-- Quick cards --}}
    <div class="quick-cards">
        <div class="container">
            <div class="grid" data-stagger>
                <a href="{{ route('site.products') }}">
                    <i class="ri-shopping-basket-2-line"></i>
                    <div><h3>Products &amp; prices</h3><p>{{ $products->count() ? 'Fresh from the farm, with today\'s prices.' : 'See what the farm sells.' }}</p></div>
                </a>
                <a href="{{ $ordersOpen ? route('site.products') . '#order' : route('site.contact') }}">
                    <i class="ri-truck-line"></i>
                    <div><h3>{{ $ordersOpen ? 'Order online' : 'Talk to us' }}</h3><p>{{ $ordersOpen ? 'Send an order; we call to confirm pick-up or delivery.' : 'Questions, bulk supply or a farm visit.' }}</p></div>
                </a>
                <a href="{{ route('site.contact') }}#visit">
                    <i class="ri-map-pin-user-line"></i>
                    <div><h3>Visit the farm</h3><p>See how we raise our birds and animals.</p></div>
                </a>
            </div>
        </div>
    </div>

    {{-- Ticker of highlights / enterprises --}}
    @if ($tickerItems->count())
        <div class="ticker marquee" style="margin-top:60px" aria-hidden="true">
            <div class="track">
                @foreach (range(1, 2) as $rep)
                    @foreach ($tickerItems as $t)<span><i class="ri-sparkling-2-fill"></i>{{ $t }}</span>@endforeach
                @endforeach
            </div>
        </div>
    @endif

    {{-- Intro (only when the farm has written it) --}}
    @if ($s('intro_body') || $s('intro_title'))
        <section class="section">
            <span class="blob" style="width:300px;height:300px;left:-120px;top:20%;background:var(--accent)"></span>
            <div class="container split">
                <div class="reveal from-left">
                    <span class="eyebrow">Welcome</span>
                    <h2>{{ $s('intro_title', 'About ' . $farm->name) }}</h2>
                    <div class="prose lead">{!! $s('intro_body') !!}</div>
                    @if ($highlights->count())
                        <ul class="checks" data-stagger>@foreach ($highlights->take(4) as $h)<li><span><b style="color:var(--ink)">{{ $h->title }}</b>@if ($h->body) — {{ $h->body }}@endif</span></li>@endforeach</ul>
                    @endif
                    <div style="display:flex;gap:12px;flex-wrap:wrap">
                        <a class="btn btn-primary" href="{{ isset($menu['about'][0]) ? route('site.page', $menu['about'][0]['slug']) : route('site.team') }}">Discover more</a>
                        <a class="btn btn-outline" href="{{ route('site.contact') }}#visit">Book a visit</a>
                    </div>
                </div>
                @php $intro = $F($s('intro_image')) ?: $heroImage; @endphp
                @if ($intro)
                    <div class="media-frame reveal from-right d2">
                        <img src="{{ $intro }}" alt="{{ $farm->name }}" loading="lazy" data-parallax="-0.04">
                        @if ($s('founded_year'))<div class="badge"><b>{{ max(1, now()->year - (int) $s('founded_year')) }}+</b>years of farming</div>@endif
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- Live numbers --}}
    @include('website.partials.stats', ['stats' => $stats])

    {{-- What the farm keeps (live from flocks and farm units) --}}
    @if ($enterprises->count())
        <section class="section soft">
            <div class="container">
                <div class="section-head center reveal">
                    <span class="eyebrow">On the farm</span>
                    <h2>What we raise and grow</h2>
                    <p class="lead">Straight from our records: the birds, animals and produce on the farm right now.</p>
                </div>
                <div class="enterprises" data-stagger>
                    @foreach ($enterprises as $e)
                        <div class="enterprise" style="--c: {{ $e->color }}" data-tilt>
                            <i class="{{ $e->icon }}"></i>
                            <h3>{{ $e->name }}</h3>
                            @if ($e->count)<div class="n" data-count="{{ $e->count }}">{{ number_format($e->count) }}</div><small style="color:var(--muted)">{{ strtolower($e->count_label) }} · {{ $e->units }} {{ strtolower($e->unit_label) }}</small>@endif
                            @if ($e->text)<p>{{ \Illuminate\Support\Str::limit($e->text, 120) }}</p>@endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Products --}}
    @if ($products->count())
        <section class="section">
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

    {{-- Values --}}
    @if ($values->count())
        <section class="section cream">
            <div class="container">
                <div class="section-head center reveal">
                    <span class="eyebrow">What we stand for</span>
                    <h2>{{ $s('values_title', 'How we farm') }}</h2>
                </div>
                <div class="grid-3">
                    @foreach ($values as $i => $v)
                        <div class="value reveal flip d{{ $i % 3 + 1 }}" data-n="{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}">
                            <i class="{{ $v->icon ?: 'ri-star-line' }}"></i>
                            <h3>{{ $v->title }}</h3>
                            @if ($v->body)<p>{{ $v->body }}</p>@endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Gallery preview --}}
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

    {{-- Testimonials --}}
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

    {{-- News & events --}}
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

    {{-- Partners --}}
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

    {{-- FAQ --}}
    @if ($faqs->count())
        <section class="section cream">
            <div class="container split" style="align-items:start">
                <div class="reveal from-left">
                    <span class="eyebrow">FAQs</span>
                    <h2>Frequently asked questions</h2>
                    <p class="lead">Can't find what you're looking for? We're happy to help.</p>
                    <div style="display:flex;gap:12px;flex-wrap:wrap">
                        <a class="btn btn-primary" href="{{ route('site.contact') }}">Ask a question</a>
                        @if ($farm->phones)<a class="btn btn-outline" href="tel:{{ preg_replace('/\s+/', '', $farm->phones[0]) }}"><i class="ri-phone-line"></i> {{ $farm->phones[0] }}</a>@endif
                    </div>
                </div>
                <div class="faq reveal from-right d2">
                    @foreach ($faqs as $i => $f)
                        <details @if ($i === 0) open @endif><summary>{{ $f->title }}</summary><div>{!! nl2br(e($f->body)) !!}</div></details>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Call to action --}}
    <section class="section" style="padding:70px 0">
        <div class="container">
            <div class="cta-band reveal zoom">
                <div>
                    <h2>{{ $ordersOpen ? 'Need eggs, birds or farm produce?' : 'Come and see the farm' }}</h2>
                    <p>{{ $ordersOpen ? 'Send us an order and we will confirm the price, pick-up or delivery by phone.' : 'Meet the team and see how we raise our birds and animals.' }}</p>
                </div>
                <div style="display:flex;gap:12px;flex-wrap:wrap">
                    @if ($ordersOpen)<a class="btn btn-light" href="{{ route('site.products') }}#order">Place an order</a>@endif
                    <a class="btn btn-ghost" href="{{ route('site.contact') }}#visit">Book a visit</a>
                </div>
            </div>
        </div>
    </section>
@endsection
