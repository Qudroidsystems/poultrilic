@extends('website.layout', ['pageTitle' => 'Products & prices'])

@php
    $s = fn ($k, $d = null) => (isset($site[$k]) && $site[$k] !== '') ? $site[$k] : $d;
    $ordersOpen = $s('orders_open', '1') === '1';
@endphp

@section('content')
    @include('website.partials.page-hero', ['title' => 'Products & prices', 'lead' => 'Fresh from ' . $farm->name . '. Prices marked "today\'s price" follow the farm\'s current price list.', 'crumbs' => [['Products', null]]])

    <section class="section">
        <div class="container">
            @if ($products->count())
                <div class="products">
                    @foreach ($products as $i => $p)
                        @include('website.partials.product-card', ['p' => $p, 'delay' => $i % 3 + 1, 'ordersOpen' => $ordersOpen])
                    @endforeach
                </div>
            @else
                <div class="empty"><i class="ri-shopping-basket-2-line"></i>Our product list is being prepared. Call or message us for what is available today.</div>
            @endif

            @if ($pages->count())
                <div class="chips" style="margin-top:40px">
                    @foreach ($pages as $pg)<a class="chip" href="{{ route('site.page', $pg->slug) }}">{{ $pg->title }} <i class="ri-arrow-right-up-line"></i></a>@endforeach
                </div>
            @endif
        </div>
    </section>

    @if ($enterprises->count())
        <section class="section soft" style="padding-top:70px;padding-bottom:70px">
            <div class="container">
                <div class="section-head center reveal"><span class="eyebrow">On the farm now</span><h2>Where your food comes from</h2></div>
                <div class="enterprises" data-stagger>
                    @foreach ($enterprises as $e)
                        <div class="enterprise" style="--c: {{ $e->color }}">
                            <i class="{{ $e->icon }}"></i>
                            <h3>{{ $e->name }}</h3>
                            @if ($e->count)<div class="n" data-count="{{ $e->count }}">{{ number_format($e->count) }}</div><small style="color:var(--muted)">{{ strtolower($e->count_label) }}</small>@endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($ordersOpen)
        <section class="section" id="order">
            <div class="container order-wrap">
                <div class="reveal from-left">
                    <span class="eyebrow">Order</span>
                    <h2>Place an order</h2>
                    <p class="lead">Tell us what you need. We call you back to confirm the price, quantity and when you'll get it.</p>
                    <ol class="steps" data-stagger>
                        <li><b>Send the order</b>Pick a product and how much you need.</li>
                        <li><b>We confirm by phone</b>Price, availability and pick-up or delivery time.</li>
                        <li><b>Collect or receive</b>Pay on pick-up or delivery, as agreed.</li>
                    </ol>
                    @if ($s('delivery_note'))<div class="note"><i class="ri-truck-line"></i> {{ $s('delivery_note') }}</div>@endif
                </div>
                <div class="reveal from-right d2">
                    <form class="panel form" method="POST" action="{{ route('site.order') }}">@csrf
                        <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
                        <div class="row2">
                            <div><label>Product *</label>
                                <select name="product" required id="orderProduct">
                                    @foreach ($products as $p)<option value="{{ $p->title }}" @selected(old('product', request('product')) === $p->title)>{{ $p->title }}{{ $p->display_price ? ' — ' . $p->display_price . ($p->subtitle ? ' ' . $p->subtitle : '') : '' }}</option>@endforeach
                                    <option value="Something else" @selected(old('product') === 'Something else')>Something else (say below)</option>
                                </select></div>
                            <div><label>Quantity *</label><input name="quantity" required value="{{ old('quantity') }}" placeholder="e.g. 10 crates"></div>
                        </div>
                        <div class="row2">
                            <div><label>Pick-up or delivery</label>
                                <div class="seg">
                                    <label><input type="radio" name="delivery" value="pickup" @checked(old('delivery', 'pickup') === 'pickup')><span>Pick-up</span></label>
                                    <label><input type="radio" name="delivery" value="delivery" @checked(old('delivery') === 'delivery')><span>Delivery</span></label>
                                </div></div>
                            <div><label>Needed on</label><input type="date" name="needed_on" value="{{ old('needed_on') }}" min="{{ date('Y-m-d') }}"></div>
                        </div>
                        <div><label>Delivery address</label><input name="address" value="{{ old('address') }}" placeholder="Only if you want delivery"></div>
                        <div class="row2">
                            <div><label>Your name *</label><input name="name" required value="{{ old('name') }}"></div>
                            <div><label>Phone *</label><input name="phone" required value="{{ old('phone') }}" inputmode="tel"></div>
                        </div>
                        <div><label>E-mail</label><input type="email" name="email" value="{{ old('email') }}"></div>
                        <div><label>Anything else?</label><textarea name="message" rows="3">{{ old('message') }}</textarea></div>
                        <div><button class="btn btn-primary" type="submit">Send order <i class="ri-send-plane-line"></i></button></div>
                    </form>
                </div>
            </div>
        </section>
    @endif

    @if ($faqs->count())
        <section class="section cream">
            <div class="container" style="max-width:860px">
                <div class="section-head center reveal"><span class="eyebrow">FAQs</span><h2>Good to know</h2></div>
                <div class="faq reveal">@foreach ($faqs as $i => $f)<details @if ($i === 0) open @endif><summary>{{ $f->title }}</summary><div>{!! nl2br(e($f->body)) !!}</div></details>@endforeach</div>
            </div>
        </section>
    @endif
@endsection
