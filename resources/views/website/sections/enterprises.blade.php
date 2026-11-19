@php $enterprises = $enterprises ?? \App\Services\Website\WebsiteContent::enterprises(); @endphp
@if ($enterprises->count())
    <section class="section {{ $tone ?? 'soft' }}">
        <div class="container">
            <div class="section-head center reveal">
                <span class="eyebrow">On the farm</span>
                <h2>{{ $title ?? 'What we raise and grow' }}</h2>
                <p class="lead">Straight from our records: the birds, animals and produce on the farm right now.</p>
            </div>
            <div class="enterprises" data-stagger>
                @foreach ($enterprises as $e)
                    <div class="enterprise" style="--c: {{ $e->color }}" data-tilt>
                        <i class="{{ $e->icon }}"></i>
                        <h3>{{ $e->name }}</h3>
                        @if ($e->count)<div class="n" data-count="{{ $e->count }}">{{ number_format($e->count) }}</div><small style="color:var(--muted)">{{ strtolower($e->count_label) }} · {{ $e->units }} {{ strtolower($e->unit_label) }}</small>@endif
                        @if ($e->text)<p>{{ \Illuminate\Support\Str::limit($e->text, 120) }}</p>@endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
