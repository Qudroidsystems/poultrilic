@php $values = \App\Services\Website\WebsiteContent::itemsOr('value'); @endphp
<section class="section {{ $tone ?? 'cream' }}">
    <div class="container">
        <div class="section-head center reveal">
            <span class="eyebrow">What we stand for</span>
            <h2>{{ $site['values_title'] ?? 'How we farm' }}</h2>
        </div>
        <div class="grid-3">
            @foreach ($values as $i => $v)
                <div class="value reveal flip d{{ $i % 3 + 1 }}" data-n="{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}" data-tilt>
                    <i class="{{ $v->icon ?: 'ri-star-line' }}"></i>
                    <h3>{{ $v->title }}</h3>
                    @if ($v->body)<p>{{ $v->body }}</p>@endif
                </div>
            @endforeach
        </div>
    </div>
</section>
