@extends('website.layout', ['pageTitle' => $type === 'event' ? 'Events' : 'News & updates'])

@section('content')
    @include('website.partials.page-hero', [
        'title' => $type === 'event' ? 'Events' : ($type === 'tip' ? 'Farm tips' : 'News & updates'),
        'lead' => 'What is happening on the farm — news, offers, farm days and tips.',
        'crumbs' => [[$type === 'event' ? 'Events' : 'News', null]],
    ])
    <section class="section">
        <div class="container">
            <div class="chips">
                <a class="chip {{ !$type && !request('category') ? 'on' : '' }}" href="{{ route('site.news') }}">All</a>
                <a class="chip {{ $type === 'news' ? 'on' : '' }}" href="{{ route('site.news', ['type' => 'news']) }}">News</a>
                <a class="chip {{ $type === 'event' ? 'on' : '' }}" href="{{ route('site.news', ['type' => 'event']) }}">Events</a>
                <a class="chip {{ $type === 'tip' ? 'on' : '' }}" href="{{ route('site.news', ['type' => 'tip']) }}">Farm tips</a>
                @foreach($categories as $c)
                    <a class="chip {{ request('category') === $c ? 'on' : '' }}" href="{{ route('site.news', ['category' => $c]) }}">{{ $c }}</a>
                @endforeach
            </div>
            @if($posts->count())
                <div class="grid-3">
                    @foreach($posts as $i => $p) @include('website.partials.post-card', ['p' => $p, 'delay' => $i % 3 + 1]) @endforeach
                </div>
                @include('website.partials.pager', ['pg' => $posts])
            @else
                <div class="soon reveal"><div class="soon-ic"><i class="ri-newspaper-line"></i></div><h3>News is on its way</h3><p>Farm news, offers, farm days and tips will appear here. Join our e-mail list to hear first.</p><a class="btn btn-primary" href="{{ route('site.products') }}">See today's products</a></div>
            @endif
        </div>
    </section>
    @include('website.sections.newsletter')
@endsection
