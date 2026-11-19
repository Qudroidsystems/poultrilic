@extends('layouts.master')

@section('content')
<div class="main-content"><div class="page-content"><div class="container-fluid">
    <x-cb.hero title="Website" icon="ri-global-line" subtitle="The farm's public website: products and prices, pages, news, gallery, and orders and messages from visitors. Numbers, stock and egg prices update by themselves from the farm records.">
        <x-slot:pills>
            <span class="cb-meta-pill"><span class="status-pill {{ $enabled ? 'st-paid' : 'st-danger' }}">{{ $enabled ? 'Website is live' : 'Website switched off' }}</span></span>
            <span class="cb-meta-pill"><i class="ri-link"></i>{{ url('/') }}</span>
        </x-slot:pills>
        <x-slot:actions><a class="cb-hero-btn" href="{{ \App\Services\Website\WebsiteContent::homeUrl() }}" target="_blank"><i class="ri-external-link-line me-1"></i>Open website</a></x-slot:actions>
    </x-cb.hero>
    @include('website.admin._nav')

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3"><x-cb.stat label="Unread messages" :value="$counts['unread']" icon="ri-mail-unread-line" accent="rose" /></div>
        <div class="col-6 col-md-3"><x-cb.stat label="New orders" :value="$counts['orders']" icon="ri-shopping-cart-2-line" accent="teal" /></div>
        <div class="col-6 col-md-3"><x-cb.stat label="Gallery photos" :value="$counts['photos']" icon="ri-image-line" accent="amber" /></div>
        <div class="col-6 col-md-3"><x-cb.stat label="Newsletter subscribers" :value="$counts['subscribers']" icon="ri-mail-add-line" accent="sky" /></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <x-cb.card title="Content" icon="ri-layout-masonry-line">
                <div class="row g-2">
                    @foreach([
                        ['products', 'Products', 'ri-shopping-basket-2-line', $counts['products']],
                        ['slides', 'Home slider', 'ri-slideshow-3-line', $counts['slides']], ['pages', 'Pages', 'ri-pages-line', $counts['pages']],
                        ['posts', 'News & events', 'ri-newspaper-line', $counts['posts']], [null, 'Gallery albums', 'ri-image-2-line', $counts['albums']],
                        ['people', 'Our team', 'ri-team-line', $counts['people']], ['testimonials', 'Testimonials', 'ri-chat-quote-line', $counts['testimonials']],
                        ['promises', 'Our promise', 'ri-shield-star-line', $counts['promises']], ['steps', 'Farm to table', 'ri-route-line', $counts['steps']],
                        ['values', 'Values', 'ri-medal-line', $counts['values']], ['highlights', 'Why buy from us', 'ri-star-smile-line', $counts['highlights']],
                        ['faqs', 'FAQs', 'ri-question-answer-line', $counts['faqs']], ['partners', 'Partners', 'ri-shake-hands-line', $counts['partners']],
                    ] as [$type, $label, $icon, $n])
                        <div class="col-sm-6">
                            <a href="{{ $type ? route('website.admin.list', $type) : route('website.admin.albums') }}" class="d-flex align-items-center gap-3 border rounded-3 p-3 text-reset h-100">
                                <span class="d-inline-grid rounded-3 bg-primary-subtle text-primary" style="width:44px;height:44px;place-items:center;font-size:1.3rem"><i class="{{ $icon }}"></i></span>
                                <span class="flex-grow-1"><span class="fw-semibold d-block">{{ $label }}</span><span class="small text-muted">{{ $n }} item(s)</span></span>
                                <i class="ri-arrow-right-s-line text-muted"></i>
                            </a>
                        </div>
                    @endforeach
                </div>
            </x-cb.card>
        </div>
        <div class="col-xl-5">
            <x-cb.card title="Latest messages" icon="ri-mail-line">
                <x-slot:tools><a href="{{ route('website.admin.messages') }}" class="btn btn-sm btn-light">All messages</a></x-slot:tools>
                @forelse($recent as $m)
                    <div class="d-flex gap-2 py-2 border-bottom">
                        <span class="badge {{ $m->type === 'order' ? 'bg-success' : ($m->type === 'visit' ? 'bg-info' : 'bg-secondary') }} align-self-start text-capitalize">{{ $m->type }}</span>
                        <div class="flex-grow-1">
                            <div class="fw-semibold {{ $m->read_at ? '' : 'text-primary' }}">{{ $m->subject ?: $m->name }}</div>
                            <div class="small text-muted">{{ $m->name }} · {{ $m->phone ?: $m->email }} · {{ $m->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted small mb-0">No messages yet. Orders, farm-visit requests and contact messages from the website appear here.</p>
                @endforelse
            </x-cb.card>
            <x-cb.card title="Quick links" icon="ri-links-line">
                <div class="d-grid gap-2">
                    <a class="btn btn-outline-primary" href="{{ route('website.admin.settings') }}"><i class="ri-palette-line me-1"></i>Colours, banner, numbers &amp; contacts</a>
                    <a class="btn btn-outline-primary" href="{{ route('website.admin.create', 'products') }}"><i class="ri-add-line me-1"></i>Add a product</a>
                    <form method="POST" action="{{ route('website.admin.starter') }}" class="d-grid">@csrf
                        <button class="btn btn-outline-secondary" title="Copies the standard wording for Our promise, Farm to table, Values and FAQs into editable items"><i class="ri-magic-line me-1"></i>Add starter wording to edit</button>
                    </form>
                    <a class="btn btn-outline-primary" href="{{ route('website.admin.create', 'posts') }}"><i class="ri-add-line me-1"></i>Write news or an event</a>
                    <a class="btn btn-outline-primary" href="{{ route('website.admin.albums') }}"><i class="ri-upload-cloud-2-line me-1"></i>Upload gallery photos</a>
                </div>
            </x-cb.card>
        </div>
    </div>
</div></div></div>
@endsection
