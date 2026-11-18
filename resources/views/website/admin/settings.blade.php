@extends('layouts.master')

@php
    $v = fn ($k, $d = '') => old($k, $s[$k] ?? $d);
    $F = fn ($p) => \App\Services\Website\WebsiteContent::fileUrl($p);
    $statsOn = array_filter(explode(',', (string) ($s['stats_items'] ?? 'birds,eggs_month,laying_rate,units,customers,years')));
@endphp

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Website settings" icon="ri-settings-4-line" subtitle="Logo, colours, the home page banner, welcome text, live numbers, ordering, contacts and social media." />
    @include('website.admin._nav')

    <form method="POST" action="{{ route('website.admin.settings.save') }}" enctype="multipart/form-data">@csrf
        <div class="row g-3">
            <div class="col-xl-8">
                <x-cb.card title="Farm identity" icon="ri-store-3-line">
                    <p class="small text-muted">The farm name, address and main phone come from <a href="{{ Route::has('settings.index') ? route('settings.index') : '#' }}">Prices &amp; Costs › Business details</a> (now: <b>{{ $farm->name }}</b>).</p>
                    <div class="row g-3">
                        <div class="col-md-8"><label class="form-label">Tagline</label><input name="tagline" class="form-control" value="{{ $v('tagline') }}" placeholder="e.g. Fresh eggs and healthy birds, every day"></div>
                        <div class="col-md-4"><label class="form-label">Year farming started</label><input name="founded_year" type="number" class="form-control" value="{{ $v('founded_year') }}" placeholder="e.g. 2015"></div>
                        <div class="col-md-12">
                            <label class="form-label">Logo</label>
                            <div class="d-flex gap-3 align-items-center">
                                @if($farm->logo)<img src="{{ $farm->logo }}" style="width:64px;height:64px;object-fit:contain;border-radius:12px;background:#f8fafc">@endif
                                <div class="flex-grow-1"><input type="file" name="logo" accept="image/*" class="form-control">
                                    @if(!empty($s['logo']))<div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="rl"><label class="form-check-label small" for="rl">Remove</label></div>@endif
                                    <div class="form-text">Square PNG works best.</div></div>
                            </div>
                        </div>
                    </div>
                </x-cb.card>

                <x-cb.card title="Home page banner" icon="ri-image-2-line">
                    <p class="small text-muted">Shown when there are no slides in <a href="{{ route('website.admin.list', 'slides') }}">Home slider</a>. Without a photo, the banner shows an animated farm scene.</p>
                    <div class="row g-3">
                        <div class="col-md-7"><label class="form-label">Heading</label><input name="hero_title" class="form-control" value="{{ $v('hero_title') }}" placeholder="{{ $farm->name }}"></div>
                        <div class="col-md-5"><label class="form-label">Button text</label><input name="hero_button_text" class="form-control" value="{{ $v('hero_button_text') }}" placeholder="See products & prices"></div>
                        <div class="col-md-7"><label class="form-label">Text under the heading</label><input name="hero_subtitle" class="form-control" value="{{ $v('hero_subtitle') }}"></div>
                        <div class="col-md-5"><label class="form-label">Button link</label><input name="hero_button_link" class="form-control" value="{{ $v('hero_button_link') }}" placeholder="/products"></div>
                        <div class="col-md-12">
                            <label class="form-label">Banner photo</label>
                            <div class="d-flex gap-3 align-items-center">
                                @if(!empty($s['hero_image']))<img src="{{ $F($s['hero_image']) }}" style="width:160px;height:90px;object-fit:cover;border-radius:10px">@endif
                                <div class="flex-grow-1"><input type="file" name="hero_image" accept="image/*" class="form-control">
                                    @if(!empty($s['hero_image']))<div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="remove_hero_image" value="1" id="rh"><label class="form-check-label small" for="rh">Remove</label></div>@endif
                                    <div class="form-text">A wide photo (at least 1600 × 900). It is resized automatically.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-cb.card>

                <x-cb.card title="Welcome section" icon="ri-home-heart-line">
                    <p class="small text-muted">Hidden until you write something.</p>
                    <label class="form-label">Heading</label>
                    <input name="intro_title" class="form-control mb-3" value="{{ $v('intro_title') }}" placeholder="About {{ $farm->name }}">
                    <label class="form-label">Text</label>
                    @include('website.admin._editor', ['name' => 'intro_body', 'value' => $v('intro_body'), 'height' => 180])
                    <div class="row g-3 mt-1">
                        <div class="col-md-8">
                            <label class="form-label">Photo</label>
                            <input type="file" name="intro_image" accept="image/*" class="form-control">
                            @if(!empty($s['intro_image']))<div class="d-flex align-items-center gap-2 mt-2"><img src="{{ $F($s['intro_image']) }}" style="width:90px;height:90px;object-fit:cover;border-radius:10px"><div class="form-check"><input class="form-check-input" type="checkbox" name="remove_intro_image" value="1" id="ri"><label class="form-check-label small" for="ri">Remove</label></div></div>@endif
                        </div>
                        <div class="col-md-4"><label class="form-label">Values heading</label><input name="values_title" class="form-control" value="{{ $v('values_title', 'How we farm') }}"></div>
                    </div>
                </x-cb.card>

                <x-cb.card title="Contacts & map" icon="ri-map-pin-line">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">E-mail</label><input name="email" type="email" class="form-control" value="{{ $v('email') }}" placeholder="sales@yourfarm.com"></div>
                        <div class="col-md-6"><label class="form-label">More phone numbers</label><input name="extra_phones" class="form-control" value="{{ $v('extra_phones') }}" placeholder="0803…, 0705…"></div>
                        <div class="col-md-6"><label class="form-label">WhatsApp number</label><input name="whatsapp" class="form-control" value="{{ $v('whatsapp') }}" placeholder="0803…"><div class="form-text">Shows a floating WhatsApp button.</div></div>
                        <div class="col-md-6"><label class="form-label">Opening hours</label><input name="office_hours" class="form-control" value="{{ $v('office_hours') }}" placeholder="Mon – Sat, 7am – 6pm"></div>
                        <div class="col-12"><label class="form-label">Google Maps embed code</label><textarea name="map_embed" class="form-control" rows="2" placeholder='<iframe src="https://www.google.com/maps/embed?pb=…"></iframe>'>{{ $v('map_embed') }}</textarea>
                            <div class="form-text">Google Maps › Share › Embed a map › Copy HTML.</div></div>
                    </div>
                </x-cb.card>
            </div>

            <div class="col-xl-4">
                <x-cb.card title="Website" icon="ri-global-line">
                    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" role="switch" name="enabled" value="1" id="en" @checked($v('enabled', '1') === '1')><label class="form-check-label fw-semibold" for="en">Website is live</label></div>
                    <p class="small text-muted mb-0">When off, the farm's address opens the staff sign-in page instead.</p>
                </x-cb.card>
                <x-cb.card title="Live numbers" icon="ri-numbers-line">
                    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" role="switch" name="show_stats" value="1" id="ss" @checked($v('show_stats', '1') === '1')><label class="form-check-label fw-semibold" for="ss">Show "The farm today"</label></div>
                    <p class="small text-muted">Numbers come from the farm records and refresh every hour. Tick what visitors may see:</p>
                    @foreach(\App\Services\Website\WebsiteContent::STAT_CHOICES as $k => $label)
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="stats_items[]" value="{{ $k }}" id="st_{{ $k }}" @checked(in_array($k, old('stats_items', $statsOn), true))><label class="form-check-label small" for="st_{{ $k }}">{{ $label }}</label></div>
                    @endforeach
                </x-cb.card>
                <x-cb.card title="Products & orders" icon="ri-shopping-cart-2-line">
                    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" role="switch" name="show_prices" value="1" id="sp" @checked($v('show_prices', '1') === '1')><label class="form-check-label" for="sp">Show prices</label></div>
                    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" role="switch" name="orders_open" value="1" id="oo" @checked($v('orders_open', '1') === '1')><label class="form-check-label" for="oo">Take orders on the website</label></div>
                    <label class="form-label small mt-2">Delivery note</label>
                    <input name="delivery_note" class="form-control form-control-sm" value="{{ $v('delivery_note') }}" placeholder="e.g. We deliver within Ikeja for orders of 10 crates or more">
                </x-cb.card>
                <x-cb.card title="Colours" icon="ri-palette-line">
                    @foreach(['primary_color' => ['Main colour', '#15803d'], 'secondary_color' => ['Dark colour', '#0f2a1d'], 'accent_color' => ['Accent colour', '#f59e0b']] as $k => [$label, $def])
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <input type="color" class="form-control form-control-color" value="{{ $v($k, $def) }}" oninput="this.nextElementSibling.value=this.value">
                            <input name="{{ $k }}" class="form-control" value="{{ $v($k, $def) }}" pattern="#[0-9a-fA-F]{6}" style="max-width:120px">
                            <span class="small">{{ $label }}</span>
                        </div>
                    @endforeach
                </x-cb.card>
                <x-cb.card title="Social media" icon="ri-share-line">
                    @foreach(['social_facebook' => 'Facebook', 'social_instagram' => 'Instagram', 'social_x' => 'X (Twitter)', 'social_youtube' => 'YouTube', 'social_tiktok' => 'TikTok'] as $k => $label)
                        <label class="form-label small mb-1">{{ $label }}</label><input name="{{ $k }}" class="form-control form-control-sm mb-2" value="{{ $v($k) }}" placeholder="https://…">
                    @endforeach
                </x-cb.card>
                <x-cb.card title="Footer & search engines" icon="ri-search-eye-line">
                    <label class="form-label">Footer text</label><textarea name="footer_about" class="form-control mb-2" rows="2">{{ $v('footer_about') }}</textarea>
                    <label class="form-label">Description for Google</label><textarea name="meta_description" class="form-control" rows="2" maxlength="300">{{ $v('meta_description') }}</textarea>
                </x-cb.card>
                <button class="btn btn-primary w-100"><i class="ri-save-3-line me-1"></i>Save settings</button>
            </div>
        </div>
    </form>
</div></div></div>
@endsection
