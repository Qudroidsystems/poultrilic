@extends('website.layout', ['pageTitle' => 'Our team'])

@php
    $F = fn ($p) => \App\Services\Website\WebsiteContent::fileUrl($p);
    $titles = ['leadership' => 'Management', 'staff' => 'Farm team', 'partners' => 'Advisers & vets'];
@endphp

@section('content')
    @include('website.partials.page-hero', ['title' => 'Our team', 'lead' => 'The people who look after the birds, the animals and our customers.', 'crumbs' => [['About', null], ['Our team', null]]])
    <section class="section">
        <div class="container">
            @forelse(['leadership', 'staff', 'partners'] as $g)
                @if(isset($people[$g]))
                    <div class="section-head center reveal" style="margin-bottom:34px"><span class="eyebrow">{{ $titles[$g] }}</span></div>
                    <div class="grid-4" style="margin-bottom:60px">
                        @foreach($people[$g] as $i => $p)
                            <div class="person reveal d{{ $i % 4 + 1 }}">
                                <div class="ph">
                                    @if($p->photo)<img src="{{ $F($p->photo) }}" alt="{{ $p->name }}" loading="lazy">
                                    @else<span class="initials">{{ collect(explode(' ', $p->name))->filter()->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}</span>@endif
                                </div>
                                <h4>{{ $p->name }}</h4>
                                @if($p->role)<small>{{ $p->role }}</small>@endif
                                @if($p->bio)<p style="font-size:.93rem;margin-top:8px">{{ \Illuminate\Support\Str::limit($p->bio, 160) }}</p>@endif
                            </div>
                        @endforeach
                    </div>
                @endif
            @empty
            @endforelse
            @if($people->isEmpty())<div class="soon reveal"><div class="soon-ic"><i class="ri-team-line"></i></div><h3>Meet the team soon</h3><p>The people who feed the birds, collect the eggs and serve our customers will be introduced here.</p><a class="btn btn-primary" href="{{ route('site.about') }}">About the farm</a></div>@endif
        </div>
    </section>
    @include('website.sections.values', ['tone' => 'cream'])
    @include('website.sections.visit')
@endsection
