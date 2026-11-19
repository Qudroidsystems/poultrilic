@php
    $faqs = \App\Services\Website\WebsiteContent::itemsOr('faq', null, $limit ?? null);
@endphp
<section class="section {{ $tone ?? 'cream' }}">
    <div class="container split" style="align-items:start">
        <div class="reveal from-left">
            <span class="eyebrow">FAQs</span>
            <h2>{{ $title ?? 'Questions customers ask' }}</h2>
            <p class="lead">Can't find your answer? We're happy to help.</p>
            <div style="display:flex;gap:12px;flex-wrap:wrap">
                <a class="btn btn-primary" href="{{ route('site.contact') }}#message">Ask a question</a>
                @if (!empty($farm->phones))<a class="btn btn-outline" href="tel:{{ preg_replace('/\s+/', '', $farm->phones[0]) }}"><i class="ri-phone-line"></i> {{ $farm->phones[0] }}</a>@endif
            </div>
        </div>
        <div class="faq reveal from-right d2">
            @foreach ($faqs as $i => $f)
                <details @if ($i === 0) open @endif><summary>{{ $f->title }}</summary><div>{!! nl2br(e($f->body)) !!}</div></details>
            @endforeach
        </div>
    </div>
</section>
