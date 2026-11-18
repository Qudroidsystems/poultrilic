{{-- Farm website shell, from csskabba-portal's school website. Everything here is dynamic:
     $site (Website › Settings), $farm (Business details from Prices & Costs) and $menu (pages). --}}
@php
    $s = fn ($k, $d = null) => (isset($site[$k]) && $site[$k] !== '') ? $site[$k] : $d;
    $title = trim(($pageTitle ?? '') ? ($pageTitle . ' — ' . $farm->name) : ($farm->name . ($farm->tagline ? ' | ' . $farm->tagline : '')));
    $about = $menu['about'] ?? [];
    $farmPages = $menu['farm'] ?? [];
    $productPages = $menu['products'] ?? [];
    $socials = array_filter([
        'facebook' => $s('social_facebook'), 'instagram' => $s('social_instagram'), 'twitter-x' => $s('social_x'),
        'youtube' => $s('social_youtube'), 'tiktok' => $s('social_tiktok'),
    ]);
    $wa = preg_replace('/[^0-9]/', '', (string) $s('whatsapp', ''));
    if ($wa && str_starts_with($wa, '0')) $wa = '234' . substr($wa, 1);
    $cur = trim(request()->path(), '/');
    $ordersOpen = $s('orders_open', '1') === '1';
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $metaDescription ?? $s('meta_description', $farm->name . ($farm->tagline ? ' — ' . $farm->tagline : '')) }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $metaDescription ?? $s('meta_description', $farm->name) }}">
    @if (!empty($ogImage ?? null) || $farm->logo)<meta property="og:image" content="{{ $ogImage ?? $farm->logo }}">@endif
    <meta name="theme-color" content="{{ $s('primary_color', '#15803d') }}">
    <link rel="icon" href="{{ $farm->logo ?: asset('theme/layouts/assets/images/favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Source+Sans+3:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="{{ asset('site/remixicon/remixicon.css') }}" rel="stylesheet">
    <link href="{{ asset('site/site.css') }}?v={{ @filemtime(public_path('site/site.css')) }}" rel="stylesheet">
    <style>:root{--primary:{{ $s('primary_color', '#15803d') }};--secondary:{{ $s('secondary_color', '#0f2a1d') }};--accent:{{ $s('accent_color', '#f59e0b') }};}</style>
    @stack('head')
</head>
<body>
    <div class="progress-bar" aria-hidden="true"></div>
    <div class="topbar">
        <div class="container">
            <div class="social">
                @foreach ($socials as $icon => $url)
                    <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $icon }}"><i class="ri-{{ $icon }}-{{ $icon === 'twitter-x' ? 'line' : 'fill' }}"></i></a>
                @endforeach
                @if (!$socials && $farm->phones)<span><i class="ri-phone-line"></i> {{ $farm->phones[0] }}</span>@endif
                @if ($s('office_hours'))<span class="d-none-sm"><i class="ri-time-line"></i> {{ $s('office_hours') }}</span>@endif
            </div>
            <div class="quick">
                <a href="{{ route('site.products') }}">Products &amp; prices</a>
                <a href="{{ route('site.contact') }}#visit">Visit the farm</a>
                <a href="{{ route('site.news') }}">News</a>
                <a href="{{ route('login') }}" class="login"><i class="ri-user-3-line"></i> Staff login</a>
            </div>
        </div>
    </div>

    <header class="header">
        <div class="container">
            <a href="{{ route('site.home') }}" class="brand">
                @if ($farm->logo)<img src="{{ $farm->logo }}" alt="{{ $farm->name }} logo">@else<span class="logo-mark"><i class="ri-seedling-fill"></i></span>@endif
                <span class="name">{{ $farm->name }}@if ($farm->tagline)<small>{{ $farm->tagline }}</small>@endif</span>
            </a>
            <button class="burger" aria-label="Open menu"><i class="ri-menu-3-line"></i></button>
            <nav class="nav" aria-label="Main">
                <button class="nav-close" aria-label="Close menu"><i class="ri-close-line"></i></button>
                <div class="item {{ $cur === '' ? 'active' : '' }}"><a href="{{ route('site.home') }}">Home</a></div>
                <div class="item has-drop {{ str_starts_with($cur, 'pages') || $cur === 'our-team' ? 'active' : '' }}">
                    <a href="{{ isset($about[0]) ? route('site.page', $about[0]['slug']) : route('site.team') }}">About <i class="ri-arrow-down-s-line"></i></a>
                    <div class="drop">
                        @foreach ($about as $p)<a href="{{ route('site.page', $p['slug']) }}">{{ $p['title'] }}</a>@endforeach
                        @foreach ($farmPages as $p)<a href="{{ route('site.page', $p['slug']) }}">{{ $p['title'] }}</a>@endforeach
                        <a href="{{ route('site.team') }}">Our team</a>
                        <a href="{{ route('site.gallery') }}">Gallery</a>
                    </div>
                </div>
                <div class="item has-drop {{ str_starts_with($cur, 'products') ? 'active' : '' }}">
                    <a href="{{ route('site.products') }}">Products <i class="ri-arrow-down-s-line"></i></a>
                    <div class="drop">
                        <a href="{{ route('site.products') }}">Products &amp; prices</a>
                        @foreach ($productPages as $p)<a href="{{ route('site.page', $p['slug']) }}">{{ $p['title'] }}</a>@endforeach
                        @if ($ordersOpen)<a href="{{ route('site.products') }}#order">Place an order</a>@endif
                    </div>
                </div>
                <div class="item {{ str_starts_with($cur, 'news') ? 'active' : '' }}"><a href="{{ route('site.news') }}">News &amp; events</a></div>
                <div class="item {{ str_starts_with($cur, 'gallery') ? 'active' : '' }}"><a href="{{ route('site.gallery') }}">Gallery</a></div>
                <div class="item {{ str_starts_with($cur, 'contact') ? 'active' : '' }}"><a href="{{ route('site.contact') }}">Contact</a></div>
                @if ($ordersOpen)<div class="item cta"><a class="btn btn-primary btn-sm" href="{{ route('site.products') }}#order" style="padding:11px 22px">Order now</a></div>@endif
            </nav>
        </div>
    </header>
    <div class="scrim"></div>

    <main>
        @yield('content')
    </main>

    <footer class="footer">
        <div class="container">
            <div class="cols">
                <div>
                    <a href="{{ route('site.home') }}" class="brand" style="margin-bottom:16px">
                        @if ($farm->logo)<img src="{{ $farm->logo }}" alt="">@else<span class="logo-mark"><i class="ri-seedling-fill"></i></span>@endif
                        <span class="name">{{ $farm->name }}</span>
                    </a>
                    <p>{{ $s('footer_about', $farm->tagline) }}</p>
                    <div class="social" style="margin-top:16px">
                        @foreach ($socials as $icon => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $icon }}"><i class="ri-{{ $icon }}-{{ $icon === 'twitter-x' ? 'line' : 'fill' }}"></i></a>
                        @endforeach
                    </div>
                </div>
                <div>
                    <h5>The farm</h5>
                    <ul>
                        @foreach ($about as $p)<li><a href="{{ route('site.page', $p['slug']) }}">{{ $p['title'] }}</a></li>@endforeach
                        @foreach (array_slice($farmPages, 0, 3) as $p)<li><a href="{{ route('site.page', $p['slug']) }}">{{ $p['title'] }}</a></li>@endforeach
                        <li><a href="{{ route('site.team') }}">Our team</a></li>
                        <li><a href="{{ route('site.gallery') }}">Gallery</a></li>
                        <li><a href="{{ route('site.news') }}">News &amp; events</a></li>
                    </ul>
                </div>
                <div>
                    <h5>Buy from us</h5>
                    <ul>
                        <li><a href="{{ route('site.products') }}">Products &amp; prices</a></li>
                        @foreach (array_slice($productPages, 0, 3) as $p)<li><a href="{{ route('site.page', $p['slug']) }}">{{ $p['title'] }}</a></li>@endforeach
                        @if ($ordersOpen)<li><a href="{{ route('site.products') }}#order">Place an order</a></li>@endif
                        <li><a href="{{ route('site.contact') }}#visit">Visit the farm</a></li>
                    </ul>
                </div>
                <div>
                    <h5>Get in touch</h5>
                    <ul>
                        @if ($farm->address)<li><i class="ri-map-pin-2-line"></i> {{ $farm->address }}</li>@endif
                        @foreach ($farm->phones as $ph)<li><i class="ri-phone-line"></i> <a href="tel:{{ preg_replace('/\s+/', '', $ph) }}">{{ $ph }}</a></li>@endforeach
                        @if ($farm->email)<li><i class="ri-mail-line"></i> <a href="mailto:{{ $farm->email }}">{{ $farm->email }}</a></li>@endif
                    </ul>
                    <form class="news" method="POST" action="{{ route('site.newsletter') }}">@csrf
                        <input type="email" name="email" required placeholder="Your e-mail for offers" aria-label="E-mail">
                        <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
                        <button type="submit">Join</button>
                    </form>
                </div>
            </div>
            <div class="bottom">
                <span>© {{ date('Y') }} {{ $farm->name }}. All rights reserved.</span>
                <span><a href="{{ route('login') }}">Staff login</a> · <a href="{{ route('site.products') }}">Products</a> · <a href="{{ route('site.contact') }}">Contact</a> · Powered by Poultrilic</span>
            </div>
        </div>
    </footer>

    @if ($wa)<a class="float-wa" href="https://wa.me/{{ $wa }}?text={{ urlencode('Hello ' . $farm->name . ', ') }}" target="_blank" rel="noopener" aria-label="Chat on WhatsApp"><i class="ri-whatsapp-line"></i></a>@endif
    <button class="to-top" aria-label="Back to top"><i class="ri-arrow-up-line"></i></button>
    @if (session('site_success'))<div class="toast" role="status"><i class="ri-checkbox-circle-fill"></i> {{ session('site_success') }}</div>@endif
    @if ($errors->any())<div class="toast" role="alert" style="background:#9b1c1c"><i class="ri-error-warning-fill"></i> {{ $errors->first() }}</div>@endif
    <script src="{{ asset('site/site.js') }}?v={{ @filemtime(public_path('site/site.js')) }}" defer></script>
    @stack('scripts')
</body>
</html>
