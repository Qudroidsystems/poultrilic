{{-- One product: photo, live availability, price (live from Prices & Costs when set), order button. --}}
@php $F = fn ($x) => \App\Services\Website\WebsiteContent::fileUrl($x); @endphp
<article class="product reveal d{{ $delay ?? 1 }}" data-tilt>
    <div class="ph">
        @if ($p->image)<img src="{{ $F($p->image) }}" alt="{{ $p->title }}" loading="lazy">
        @elseif ($p->stock_source === 'eggs' || str_contains(strtolower($p->title), 'egg'))<span class="ico"><span class="egg-ico"></span></span>
        @else<span class="ico"><i class="{{ $p->icon ?: 'ri-seedling-fill' }}"></i></span>@endif
        @if ($p->stock)<span class="stock {{ $p->stock[1] ? 'ok' : '' }}">{{ $p->stock[0] }}</span>@endif
    </div>
    <div class="body">
        <h3>{{ $p->title }}</h3>
        @if ($p->subtitle)<span class="unit">{{ $p->subtitle }}</span>@endif
        @if ($p->body)<p>{{ \Illuminate\Support\Str::limit($p->body, 150) }}</p>@else<p></p>@endif
        <div class="foot">
            @if ($p->display_price)
                <span class="price">{{ $p->display_price }}<small>{{ $p->subtitle ?: 'price' }}@if ($p->price_source) · <span class="live-price"><i class="ri-refresh-line"></i>today's price</span>@endif</small></span>
            @else
                <span class="price ask">Ask for price</span>
            @endif
            @if ($ordersOpen ?? true)
                <a class="btn btn-primary btn-sm" href="{{ route('site.products') }}?product={{ urlencode($p->title) }}#order" data-order="{{ $p->title }}">Order</a>
            @endif
        </div>
    </div>
</article>
