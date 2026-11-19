{{-- Bulk buyers: shops, hotels, bakeries, caterers. --}}
<section class="section bulk-sec">
    <div class="container">
        <div class="bulk reveal zoom">
            <div class="bulk-icons" aria-hidden="true">
                <i class="ri-store-2-line"></i><i class="ri-hotel-line"></i><i class="ri-cake-3-line"></i><i class="ri-restaurant-2-line"></i>
            </div>
            <div class="bulk-text">
                <span class="eyebrow" style="color:var(--accent)">Bulk &amp; wholesale</span>
                <h2>Supplying a shop, hotel, bakery or caterer?</h2>
                <p>Tell us how many crates you need each week. We'll agree a steady supply and a bulk price.</p>
            </div>
            <div class="bulk-cta">
                <a class="btn btn-accent" href="{{ route('site.contact') }}?subject={{ urlencode('Bulk supply') }}#message">Ask for a bulk price</a>
                @if (!empty($farm->phones))<a class="btn btn-ghost" href="tel:{{ preg_replace('/\s+/', '', $farm->phones[0]) }}"><i class="ri-phone-line"></i> {{ $farm->phones[0] }}</a>@endif
            </div>
        </div>
    </div>
</section>
