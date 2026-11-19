@php $ordersOpen = ($site['orders_open'] ?? '1') === '1'; @endphp
<section class="section" style="padding:70px 0">
    <div class="container">
        <div class="cta-band reveal zoom">
            <div>
                <h2>{{ $title ?? ($ordersOpen ? 'Need eggs, birds or farm produce?' : 'Come and see the farm') }}</h2>
                <p>{{ $lead ?? ($ordersOpen ? 'Send us an order and we will confirm the price, pick-up or delivery by phone.' : 'Meet the team and see how we raise our birds and animals.') }}</p>
            </div>
            <div style="display:flex;gap:12px;flex-wrap:wrap">
                @if ($ordersOpen)<a class="btn btn-light" href="{{ route('site.products') }}#order">Place an order</a>@endif
                <a class="btn btn-ghost" href="{{ route('site.contact') }}#visit">Book a visit</a>
            </div>
        </div>
    </div>
</section>
