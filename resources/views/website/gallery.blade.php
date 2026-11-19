@extends('website.layout', ['pageTitle' => 'Gallery'])

@php $F = fn ($p) => \App\Services\Website\WebsiteContent::fileUrl($p); @endphp

@section('content')
    @include('website.partials.page-hero', ['title' => 'Gallery', 'lead' => 'Life on the farm: our birds, animals, harvests and people.', 'crumbs' => [['Gallery', null]]])
    <section class="section">
        <div class="container">
            @if($albums->count())
                <div class="grid-3">
                    @foreach($albums as $i => $a)
                        @php $cover = $a->coverPhoto(); @endphp
                        <a class="card album-card reveal d{{ $i % 3 + 1 }}" href="{{ route('site.album', $a->slug) }}" style="color:inherit">
                            <div class="thumb">
                                @if($cover)<img src="{{ $F($cover->thumb ?: $cover->path) }}" alt="{{ $a->title }}" loading="lazy">@endif
                                <span class="count"><i class="ri-image-line"></i> {{ $a->photos_count }} photos</span>
                            </div>
                            <div class="body">
                                <div class="meta">@if($a->taken_on)<span><i class="ri-calendar-line"></i> {{ $a->taken_on->format('d M Y') }}</span>@endif</div>
                                <h3 style="margin:0">{{ $a->title }}</h3>
                                @if($a->description)<p style="margin:8px 0 0">{{ \Illuminate\Support\Str::limit($a->description, 110) }}</p>@endif
                            </div>
                        </a>
                    @endforeach
                </div>
                @include('website.partials.pager', ['pg' => $albums])
            @else
                <div class="soon reveal"><div class="soon-ic"><i class="ri-camera-3-line"></i></div><h3>Photos coming soon</h3><p>Pictures of our birds, pens, harvests and team will be shared here. Better still — come and see the farm yourself.</p><a class="btn btn-primary" href="{{ route('site.contact') }}#visit">Book a visit</a></div>
            @endif
        </div>
    </section>
    @include('website.sections.process', ['tone' => 'cream'])
    @include('website.sections.cta')
@endsection
