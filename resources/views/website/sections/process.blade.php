{{-- Farm to table: numbered steps joined by a line that draws itself as you scroll. --}}
@php $steps = \App\Services\Website\WebsiteContent::itemsOr('step', null, 6); @endphp
<section class="section {{ $tone ?? '' }}">
    <span class="blob" style="width:320px;height:320px;left:-140px;bottom:0;background:var(--accent)"></span>
    <div class="container">
        <div class="section-head center reveal">
            <span class="eyebrow">Farm to table</span>
            <h2>{{ $title ?? 'How your eggs get to you' }}</h2>
            <p class="lead">{{ $lead ?? 'A simple, careful routine — every day, for every flock.' }}</p>
        </div>
        <div class="process" data-draw>
            <svg class="process-line" viewBox="0 0 1000 10" preserveAspectRatio="none" aria-hidden="true"><path d="M0,5 L1000,5"/></svg>
            <div class="process-grid" data-stagger style="--n: {{ $steps->count() }}">
                @foreach ($steps as $i => $st)
                    <div class="pstep">
                        <div class="pnum"><i class="{{ $st->icon ?: 'ri-checkbox-circle-line' }}"></i><span>{{ $i + 1 }}</span></div>
                        <h3>{{ $st->title }}</h3>
                        @if ($st->body)<p>{{ $st->body }}</p>@endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
