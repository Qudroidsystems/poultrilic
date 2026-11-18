{{-- Inner-page banner: @include('website.partials.page-hero', ['title' => ..., 'lead' => ..., 'crumbs' => [['Label', url], ...], 'image' => url]) --}}
<section class="page-hero {{ !empty($image) ? 'has-img' : '' }}" @if (!empty($image)) style="background-image:url('{{ $image }}')" @endif>
    <span class="blob" style="width:340px;height:340px;right:-80px;top:-120px;background:var(--accent)"></span>
    <span class="blob" style="width:260px;height:260px;left:10%;bottom:-160px;background:var(--primary);animation-delay:-6s"></span>
    <div class="container">
        <nav class="crumbs reveal" aria-label="Breadcrumb">
            <a href="{{ route('site.home') }}">Home</a>
            @foreach (($crumbs ?? []) as [$label, $link])
                <span>/</span>@if ($link)<a href="{{ $link }}">{{ $label }}</a>@else<span style="color:#fff">{{ $label }}</span>@endif
            @endforeach
        </nav>
        <h1 class="split-words">{{ $title }}</h1>
        @if (!empty($lead))<p class="reveal d2">{{ $lead }}</p>@endif
    </div>
</section>
