{{-- Trust badges ("Our promise"): the farm's own, or plain defaults. --}}
@php $items = \App\Services\Website\WebsiteContent::itemsOr('promise', null, 4); @endphp
<section class="promise-strip">
    <div class="container">
        <div class="promise-grid" data-stagger>
            @foreach ($items as $it)
                <div class="promise">
                    <span class="ic"><i class="{{ $it->icon ?: 'ri-check-double-line' }}"></i></span>
                    <div><b>{{ $it->title }}</b>@if ($it->body)<small>{{ $it->body }}</small>@endif</div>
                </div>
            @endforeach
        </div>
    </div>
</section>
